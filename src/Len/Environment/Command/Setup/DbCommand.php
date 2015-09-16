<?php
/**
 * Setup command for Magento databases.
 *
 * @package Len\Environment\Command\Setup
 */

namespace Len\Environment\Command\Setup;

use \Len\Environment\Byte\Service\ByteServiceClient;
use \Len\Environment\Byte\Service\DatabaseBackup;
use \Len\Environment\Byte\Service\Registrant;
use \Len\Environment\Credentials\ByteCredentials;
use \Len\Environment\Credentials\DatabaseCredentials;
use \N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Helper\QuestionHelper;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Question\ChoiceQuestion;
use \Symfony\Component\Console\Question\ConfirmationQuestion;
use \Symfony\Component\Console\Question\Question;

/**
 * Setup command for Magento databases.
 */
class DbCommand extends AbstractMagentoCommand
{
    /**
     * The file name for import files to look for by default.
     *
     * @var string DEFAULT_IMPORT_FILE_NAME
     */
    const DEFAULT_IMPORT_FILE_NAME = 'import.sql';

    /**
     * The credentials used to authenticate with the database.
     *
     * @var DatabaseCredentials $_databaseCredentials
     */
    protected $_databaseCredentials;

    /**
     * The path to the database client binary.
     *
     * @var string $_databaseClient
     */
    protected $_databaseClient;

    /**
     * The Byte Service Client.
     *
     * @var ByteServiceClient $_byteClient
     */
    protected $_byteClient;

    /**
     * Configure the command.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('len:setup:db');
        $this->setDescription('Installs a new DB');
        $this->addOption(
            'use-local-xml',
            'l',
            InputOption::VALUE_NONE,
            'Use the local.xml for database credentials'
        );

        $this->addOption(
            'skip-byte-auth',
            'b',
            InputOption::VALUE_NONE,
            'Skip authentication using ' . ByteServiceClient::AUTH_FILE
        );
    }

    /**
     * Execute the Db setup command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $credentials = $this->fetchDatabaseCredentials($input, $output);
        $this->createDatabaseIfNotExists();

        $questionHelper = $this->getHelper('question');

        if (!($questionHelper instanceof QuestionHelper)) {
            throw new \RuntimeException(
                'Received wrong helper entity: ' . get_class($questionHelper)
            );
        }

        if (!$input->getOption('use-local-xml')) {
            $updateCredentialsQuestion = new ConfirmationQuestion(
                'Update the database credentials in local.xml? [Y|n] '
            );

            if ($questionHelper->ask(
                $input,
                $output,
                $updateCredentialsQuestion
            )) {
                $this->updateLocalCredentials($credentials);
                $output->writeln(
                    'Updated local.xml with new database credentials'
                );
            }
        }

        $importQuestion = new ConfirmationQuestion(
            'Would you like to import data? [Y|n] '
        );

        if ($questionHelper->ask($input, $output, $importQuestion)) {
            $this->processImportRequest(
                $questionHelper,
                $input,
                $output
            );
        }

        $output->writeln(
            'Finished setting up database: '
            . "<comment>{$credentials->getDatabase()}</comment>"
        );
    }

    /**
     * Create a new byte service client.
     *
     * @param QuestionHelper $questionHelper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return ByteServiceClient
     * @throws \UnexpectedValueException when one of the factory techniques
     *   delivers anything but an instance of ByteServiceClient.
     */
    protected function createByteClient(
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        if (!$input->getOption('skip-byte-auth')) {
            $authFile = ByteServiceClient::AUTH_FILE;

            try {
                $client = ByteServiceClient::fromAuthFile();
            } catch (\Exception $e) {
                $client = null;
            }

            if (isset($client)) {
                $output->writeln(
                    "Using credentials from: <comment>{$authFile}</comment>"
                );
            }
        }

        if (!isset($client)) {
            $client = new ByteServiceClient(
                ByteCredentials::fromQuestionHelper(
                    $questionHelper,
                    $input,
                    $output
                )
            );
        }

        if (!($client instanceof ByteServiceClient)) {
            throw new \UnexpectedValueException(
                'Expected instance of ByteServiceClient!'
            );
        }

        return $client;
    }

    /**
     * Getter for the _byteClient property.
     *
     * @return ByteServiceClient
     * @throws \LogicException when property _byteClient is not set.
     */
    protected function getByteClient()
    {
        if (!isset($this->_byteClient)) {
            throw new \LogicException('Missing property _byteClient');
        }

        return $this->_byteClient;
    }

    /**
     * Setter for the _byteClient property.
     *
     * @param ByteServiceClient $byteClient
     * @return static
     */
    protected function setByteClient(ByteServiceClient $byteClient)
    {
        $this->_byteClient = $byteClient;

        return $this;
    }

    /**
     * Process the custom requirements for importing data into the current
     * database.
     *
     * @param QuestionHelper $questionHelper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \LogicException when an unsupported import method was chosen.
     */
    protected function processImportRequest(
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $importMethods = ['live', 'staging', 'file'];
        $byteMethods = ['live', 'staging'];

        $importMethodQuestion = new ChoiceQuestion(
            'What is the source? ',
            $importMethods
        );

        $method = $questionHelper->ask($input, $output, $importMethodQuestion);

        // Ensure there is a byte client at this point.
        if (in_array($method, $byteMethods, true)) {
            try {
                $this->getByteClient();
            } catch (\LogicException $e) {
                $this->setByteClient(
                    $this->createByteClient(
                        $questionHelper,
                        $input,
                        $output
                    )
                );
            }
        }

        switch ($method) {
            case 'file':
                $this->processFileImportRequest(
                    $questionHelper,
                    $input,
                    $output
                );
                break;
            case 'staging':
                $this->processByteStagingImportRequest(
                    $questionHelper,
                    $input,
                    $output
                );
                break;
            case 'live':
                $this->processByteLiveImportRequest(
                    $questionHelper,
                    $input,
                    $output
                );
                break;
            default:
                throw new \LogicException(
                    "Missing implementation for import method: {$method}"
                );
        }
    }

    /**
     * Process the import request for live Byte environment.
     *
     * @param QuestionHelper $questionHelper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \RuntimeException when a registrant could not be selected.
     */
    protected function processByteLiveImportRequest(
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $client = $this->getByteClient();

        $registrants = $client->getRegistrants();

        $registrantName = $questionHelper->ask(
            $input,
            $output,
            new ChoiceQuestion(
                'Please choose a registrant: ',
                array_map(
                    function (Registrant $registrant) {
                        return $registrant->getName();
                    },
                    $registrants
                )
            )
        );

        foreach ($registrants as $currentRegistrant) {
            if ($currentRegistrant->getName() === $registrantName) {
                $registrant = $currentRegistrant;
                break;
            }
        }

        if (!isset($registrant)) {
            throw new \RuntimeException('Invalid registrant offset');
        }

        $this->processByteRegistrantImportRequest(
            $registrant,
            $questionHelper,
            $input,
            $output
        );
    }

    /**
     * Process the import request for staging Byte environment.
     *
     * @param QuestionHelper $questionHelper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function processByteStagingImportRequest(
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $output->writeln(
            '<comment>Guessing staging account based on Byte '
            . 'credentials. . .</comment>'
        );

        try {
            $registrant = $this
                ->getByteClient()
                ->getMainRegistrant();
        } catch (\OutOfBoundsException $e) {
            $registrant = null;
        }

        if ($registrant instanceof Registrant) {
            $this->processByteRegistrantImportRequest(
                $registrant,
                $questionHelper,
                $input,
                $output
            );
        } else {
            $output->writeln(
                '<error>Could not automatically determine staging '
                . 'account</error>'
            );
            $output->writeln(
                '<info>You can try again using the "live" method</info>'
            );
        }
    }

    /**
     * Process an import request for a Byte registrant.
     *
     * @param Registrant $registrant
     * @param QuestionHelper $questionHelper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function processByteRegistrantImportRequest(
        Registrant $registrant,
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $client = $this->getByteClient();

        $output->writeln(
            "Selected registrant <comment>{$registrant->getName()}</comment>"
        );

        $domains = $client->getDomains($registrant);

        $domain = current($domains);

        if (count($domains) > 1) {
            $domain = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Database domain: '
                    . "<comment>[{$domain}]</comment> ",
                    $domains
                )
            );
        }

        $output->writeln(
            "Requesting database list for <comment>{$domain}</comment>. . ."
        );

        $databases = $client->getDatabases($domain);

        if (empty($databases)) {
            throw new \RuntimeException(
                "Could not fetch databases for domain: {$domain}"
            );
        }

        $database = current($databases);

        if (count($databases) > 1) {
            // Let the user select a database.
            $database = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Which database do you prefer? '
                    . "[<comment>{$database}</comment>]",
                    $databases,
                    $database
                )
            );
        }

        $output->writeln(
            "Fetching backup list for <comment>{$database}</comment>. . ."
        );

        $backups = $client->getDatabaseBackups($domain, $database);

        $maximumBackups = 20;
        $numBackups = count($backups);

        if ($numBackups > $maximumBackups) {
            $output->writeln(
                "Found <error>{$numBackups}</error>. Only using the first "
                . "<comment>{$maximumBackups}</comment>"
            );
        }

        // Put a limit of on the backup list.
        $backups = array_slice($backups, 0, $maximumBackups);

        if (empty($backups)) {
            throw new \RuntimeException(
                "Could not fetch database backup list for: {$database}"
            );
        }

        /** @var DatabaseBackup $backup */
        $backup = current($backups);

        if (count($backups) > 1) {
            $backupOptions = array_map(
                function (DatabaseBackup $backup) {
                    $size = (int) round(
                        $backup->getSize() / pow(1024, 2)
                    );

                    return $backup
                        ->getDate()
                        ->format('D j M Y H:i')
                    . " <comment>{$size} MB</comment>";
                },
                $backups
            );

            $backupQuestion = new ChoiceQuestion(
                'Which backup would you like to use? ',
                $backupOptions
            );

            $selectedBackup = $questionHelper->ask(
                $input,
                $output,
                $backupQuestion
            );

            $backupIndex = array_search($selectedBackup, $backupOptions);

            if (!array_key_exists($backupIndex, $backups)) {
                throw new \RuntimeException(
                    'Offset does not exist in the list of available backups.'
                );
            }

            $backup = $backups[$backupIndex];
        }

        $archive = $backup->getTempFileName();

        if (file_exists($archive)
            && filesize($archive) === $backup->getSize()
        ) {
            $output->writeln(
                "Using existing file: <comment>{$archive}</comment>"
            );
        } else {
            $output->writeln(
                "Downloading <comment>{$backup->getFileName()}</comment>. . ."
            );

            if (!$client->downloadBackup($backup)) {
                throw new \RuntimeException(
                    "Failed to download {$backup->getTempFileName()}"
                );
            }
        }

        $archiveFiles = $client->listBackupFiles($backup);

        if (count($archiveFiles) !== 1) {
            throw new \RuntimeException(
                "Could not determine SQL dump file in {$archive}"
            );
        }

        $importFile = current($archiveFiles);

        $output->writeln(
            "Identified SQL dump in archive: <comment>{$importFile}</comment>"
        );

        $output->writeln(
            "Extracting archive <comment>{$archive}</comment>. . ."
        );

        $client->extractBackup($backup);

        $this->rewriteDatabaseDefiner($importFile, $output);
        $this->importFile($importFile, $output);

        $output->writeln(
            "Removing temporary dump file <comment>{$importFile}</comment>"
        );
        unlink($importFile);
    }

    /**
     * Scan a given file for a given pattern and return the results.
     *
     * @param string $file
     * @param string $pattern
     * @return mixed
     * @throws \InvalidArgumentException when $file is not a string.
     * @throws \InvalidArgumentException when $pattern is not a string.
     * @throws \RuntimeException when the grep binary is not installed.
     */
    protected function scanFile($file, $pattern)
    {
        if (!is_string($file)) {
            throw new \InvalidArgumentException(
                'Invalid file supplied: ' . gettype($file)
            );
        }

        if (!is_string($pattern)) {
            throw new \InvalidArgumentException(
                'Invalid pattern supplied: ' . gettype($pattern)
            );
        }

        static $binary;

        if (!isset($binary)) {
            $binary = trim(`which grep`);
        }

        if (empty($binary)) {
            throw new \RuntimeException('Cannot proceed without grep');
        }

        $file = escapeshellarg($file);
        $pattern = escapeshellarg($pattern);

        exec("{$binary} {$pattern} {$file}", $rv);

        return $rv;
    }

    /**
     * Rewrite the definer of triggers and functions inside SQL import files.
     *
     * @param string $importFile
     * @param OutputInterface $output
     * @return void
     * @throws \RuntimeException when $importFile is not a string, not a
     *  readable file, not a writable file or a directory.
     */
    protected function rewriteDatabaseDefiner(
        $importFile,
        OutputInterface $output
    )
    {
        if (!is_string($importFile)
            || !is_readable($importFile)
            || !is_writable($importFile)
            || is_dir($importFile)
        ) {
            throw new \RuntimeException(
                'Cannot find or alter supplied import file: '
                . var_export($importFile, true)
            );
        }

        $credentials = $this->getDatabaseCredentials();
        $newDefiner = "`{$credentials->getUser()}`";

        $output->writeln(
            "Updating definers in <comment>{$newDefiner}</comment>"
        );

        $needsRewrite = false;

        try {
            $scans = $this->scanFile($importFile, 'DEFINER=');
        } catch (\RuntimeException $e) {
            $output->writeln(
                '<error>Failed to scan the file. Proceeding ahead</error>'
            );
            $needsRewrite = true;
        }

        if (isset($scans) && is_array($scans) && count($scans) > 0) {
            $numDefiners = count($scans);
            $output->writeln(
                "Found <comment>{$numDefiners}</comment> definers to update"
            );
            $needsRewrite = true;
        }

        if ($needsRewrite === true) {
            $source = "{$importFile}.src";

            // Create a copy of the import file to read from.
            copy($importFile, $source);

            $inputHandle = fopen($source, 'r');
            $outputHandle = fopen($importFile, 'w');

            while (!feof($inputHandle)) {
                fwrite(
                    $outputHandle,
                    preg_replace_callback(
                        '/DEFINER\=([^\@]+)\@/',
                        function (array $matches) use ($newDefiner, $output) {
                            list($original, $oldDefiner) = $matches;
                            $rv = str_replace(
                                $oldDefiner,
                                $newDefiner,
                                $original
                            );

                            if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE) {
                                $output->writeln(
                                    "<error>{$original}</error> => "
                                    . "<comment>{$rv}</comment>"
                                );
                            }

                            return $rv;
                        },
                        fgets($inputHandle)
                    )
                );
            }

            fclose($inputHandle);
            fclose($outputHandle);
            unlink($source);
        }
    }

    /**
     * Process the request to import data through a dump file.
     *
     * @param QuestionHelper $questionHelper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \RuntimeException when the specified file could not be opened
     *   after 10 tries.
     */
    protected function processFileImportRequest(
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $defaultName = static::DEFAULT_IMPORT_FILE_NAME;
        $defaultFile = realpath($defaultName);
        $file = null;

        if (file_exists($defaultFile)
            && is_readable($defaultFile)
            && !is_dir($defaultFile)
        ) {
            $defaultQuestion = new ConfirmationQuestion(
                "Decected {$defaultName} file. Should we use that? "
            );

            if ($questionHelper->ask($input, $output, $defaultQuestion)) {
                $file = $defaultFile;
            }
        }

        if (!isset($file)) {
            $fileQuestion = new Question(
                "Please enter the path to the dump file: "
            );

            $maxAttempts = 10;
            $numAttempts = 0;

            while ((!is_readable($file) || is_dir($file))
                && ++$numAttempts <= $maxAttempts
            ) {
                $file = realpath(
                    $questionHelper->ask($input, $output, $fileQuestion)
                );

                if (!is_readable($file) || is_dir($file)) {
                    $output->writeln(
                        '<error>Could not open specified file!</error>'
                    );
                }
            }
        }

        if (!is_readable($file) || is_dir($file)) {
            throw new \RuntimeException('Could not open specified file!');
        }

        $this->importFile($file, $output);
    }

    /**
     * Get credentials for the destination database.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return DatabaseCredentials
     */
    protected function fetchDatabaseCredentials(
        InputInterface $input,
        OutputInterface $output
    )
    {
        if (!isset($this->_databaseCredentials)) {
            if ($input->getOption('use-local-xml')) {
                $credentials = DatabaseCredentials::fromApplication(
                    $this->getApplication()
                );
                $output->writeln(
                    'Using database credentials from '
                    . '<comment>app/etc/local.xml</comment>'
                );
            } else {
                $credentials = DatabaseCredentials::fromCommand(
                    $this,
                    $input,
                    $output
                );
            }

            $this->_databaseCredentials = $credentials;
        }

        return $this->_databaseCredentials;
    }

    /**
     * Getter for the _databaseCredentials property.
     *
     * @return DatabaseCredentials
     * @throws \LogicException when property _databaseCredentials is not set.
     */
    protected function getDatabaseCredentials()
    {
        if (!isset($this->_databaseCredentials)) {
            throw new \LogicException('Missing property _databaseCredentials');
        }

        return $this->_databaseCredentials;
    }

    /**
     * Update the local.xml file with the current database credentials.
     *
     * @param DatabaseCredentials $credentials
     * @return void
     */
    public function updateLocalCredentials(DatabaseCredentials $credentials)
    {
        $credentials->saveToApplication(
            $this->getApplication()
        );
    }

    /**
     * Create the database if it does not exist.
     *
     * @return void
     */
    protected function createDatabaseIfNotExists()
    {
        $credentials = $this->getDatabaseCredentials();

        $this->executeDatabaseQuery(
            "CREATE DATABASE IF NOT EXISTS `{$credentials->getDatabase()}` "
            . 'CHARSET UTF8',
            // Don't select the database we're about to create.
            false
        );
    }

    /**
     * Getter for the _databaseClient property.
     *
     * @return string
     * @throws \RuntimeException when the local environment has no mysql client.
     */
    public function getDatabaseClient()
    {
        if (!isset($this->_databaseClient)) {
            $client = trim(`which mysql`);

            if (empty($client)) {
                throw new \RuntimeException(
                    'Local environment has no mysql client installed!'
                );
            }

            $this->_databaseClient = $client;
        }

        return $this->_databaseClient;
    }

    /**
     * Execute the given database query.
     *
     * @param string $sql
     * @param boolean $selectDatabase
     * @return void
     * @throws \InvalidArgumentException when $selectDatabase is not a boolean.
     * @throws \RuntimeException when the query fails.
     * @todo Transform this logic into a MySQL client helper, much like the
     *   ByteServiceClient.
     */
    protected function executeDatabaseQuery($sql, $selectDatabase = true)
    {
        if (!is_bool($selectDatabase)) {
            throw new \InvalidArgumentException(
                'Invalid flag selectDatabase: ' . gettype($selectDatabase)
            );
        }

        // Prepare the database client executable by expanding the binary
        // file with database credentials.
        $executable = $this
            ->getDatabaseCredentials()
            ->expandToClient(
                $this->getDatabaseClient(),
                $selectDatabase
            );

        // Escape the query as a command line argument.
        $query = escapeshellarg($sql);

        exec(
            "echo {$query} | {$executable} > /dev/null 2>&1 || echo ERROR",
            $output
        );

        // If the output holds nothing, the query succeeded.
        if (!empty($output)) {
            throw new \RuntimeException(
                "Query could not be executed: {$sql}"
            );
        }
    }

    /**
     * Import a given SQL dump file.
     *
     * @param string $file
     * @param OutputInterface $output
     * @return void
     * @throws \RuntimeException if the file could not be imported
     * @todo Transform this logic into a MySQL client helper, much like the
     *   ByteServiceClient.
     */
    protected function importFile($file, OutputInterface $output)
    {
        if (!is_readable($file) || is_dir($file)) {
            throw new \RuntimeException('Could not open specified file!');
        }

        $output->writeln(
            "Importing database from file: <comment>{$file}</comment>"
        );

        // Prepare the database client executable by expanding the binary
        // file with database credentials.
        $executable = $this
            ->getDatabaseCredentials()
            ->expandToClient(
                $this->getDatabaseClient()
            );

        exec(
            "{$executable} < {$file} > /dev/null 2>&1 || echo ERROR",
            $output
        );

        // If the output holds nothing, the query succeeded.
        if (!empty($output)) {
            throw new \RuntimeException(
                "File could not be imported: {$file}"
            );
        }
    }
}

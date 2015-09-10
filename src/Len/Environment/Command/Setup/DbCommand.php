<?php

namespace Len\Environment\Command\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbCommand extends AbstractMagentoCommand 
{
    /**
     * The URL to the Byte DB backup API
     *
     * @var string
     */
    protected $byteUrl = 'https://service.byte.nl/';

    /**
     * The byte user to user for retrieving DBs
     *
     * @var string
     */
    protected $byteUser;

    /**
     * The byte password to user for retrieving DBs
     * @var string
     */
    protected $bytePassword;

    /**
     * DB User of local DB
     *
     * @var string
     */
    protected $dbUser = 'root';

    /**
     * DB Password of local DB
     *
     * @var string
     */
    protected $dbPassword = '';

    /**
     * DB Host of local DB
     *
     * @var string
     */
    protected $dbHost = 'localhost';

    /**
     * DB Name of local DB
     *
     * @var string
     */
    protected $dbName = '';

    /**
     * Configure the command
     * @return void
     */
    public function configure() 
    {
        $this->setName('len:setup:db')
            ->setDescription('Installs a new DB');
    }

    /**
    * Execute the Db setup command
    *
    * @param \Symfony\Component\Console\Input\InputInterface   $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    *
    * @return int|void
    */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        // Get DB Creds
        $this->getDbCredentials($output);

        // Does database already exist?
        if ($dialog->askConfirmation(
            $output,
            '<question>Do you want to use an existing database?</question>',
            false
        )) {
            // Update local.xml file
            $this->updateLocalxml();
        } else {
            // Create DB and update local.xml
            $this->createDatabase();
            $this->updateLocalxml();
        }

        // New data?
        if ($dialog->askConfirmation(
            $output,
            '<question>Would you like to use new data?</question>',
            true
        )) {
            // What way would you like to import the DB? (byte, staging, file)
            $availableMethods = array('byte','staging', 'file');
            $method = $dialog->select(
                $output,
                '<question>Where would you like to get your database from?</question>',
                $availableMethods
            );

            if ($method === '0' || $method === '1') {
                // Get Byte file
                $this->getByteDb($output, ($method === 'staging'));

            } else {
                // Import file
                $filename = $this->getFileName($output);

                $this->importFile($filename);
            }

        } else {
            return true;
        }

    }

    /**
     * Get Credentials for the local DB
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    public function getDbCredentials(OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        $this->dbUser = $dialog->ask(
            $output,
            '<question>What is the database Username?</question>',
            $this->dbUser
        );

        $this->dbPassword = $dialog->askHiddenResponse(
            $output,
            '<question>What is the database Password for user '.$this->dbUser.'?</question>',
            false
        );

        $this->dbHost = $dialog->ask(
            $output,
            '<question>What is the local database host?</question>',
            $this->dbHost
        );

        $this->dbName = $dialog->ask(
            $output,
            '<question>What is the local database name?</question>',
            $this->dbName
        );

        //@TODO validate input
    }

    /**
     * Update the local.xml file with the current creds
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    public function updateLocalxml()
    {
        $magentoRoot = $this->getApplication()->getMagentoRootFolder();
        $localXmlPath = $magentoRoot.'/app/etc/local.xml';

        $localXml = simplexml_load_file($localXmlPath);

        $localXml->config->global->resources->default_setup->connection->host = $this->dbHost;
        $localXml->config->global->resources->default_setup->connection->username = $this->dbUser;
        $localXml->config->global->resources->default_setup->connection->password = $this->dbPassword;
        $localXml->config->global->resources->default_setup->connection->dbname = $this->dbName;

        $localXml->asXML($localXmlPath);
    }

     /**
     * Create a database
     *
     * @param string $dbName 
     *
     * @return void
     */
    public function createDatabase()
    {
        //@TODO check if db exists
        $command = 'echo "CREATE DATABASE '.$this->dbName.'; " | mysql -u '.$this->dbUser. ' -p'.$this->dbPassword.' -h '.$this->dbHost;
        passthru($command);
    }

    public function getFilename(OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        $filename = $dialog->ask(
            $output,
            '<question>What file would you like to import?</question>'
        );

        return $filename;
    }

    /**
     * Import an .sql file
     *
     * @param string $fileName
     *
     * @return void
     */
    public function importFile($fileName)
    {
        $command = 'mysql -u '.$this->dbUser. '-p'.$this->dbPassword.' -h '.$this->dbHost.' < '.$fileName; passthru($command);

        passthru('rm '.$fileName);
    }

    /**
     * Gets a database from Byte
     * 
     * @param boolean $staging
     *
     */
    public function getByteDb($output, $staging = false)
    {
        //@TODO make seperate command
        $dialog = $this->getHelper('dialog');

        // Get byte creds
        $this->byteUser = $dialog->ask(
            $output,
            '<question>What is your byte username?</question>'
        );

        $this->bytePassword = $dialog->askHiddenResponse(
            $output,
            '<question>What is your byte password?</question>',
            false
        );

        // Login
        $this->byteLogin();
        // Select DB
        $db = $this->getSelectedDb($output);
        // Fetch DB
        //$this->downloadAndImport($db);
    }

    /**
     * Login to byte
     *
     * @throws \Exception
     * @return void
     */
    public function byteLogin()
    {
        $postData = ['destination' => '/protected/overzicht',
                     'credential_0' => $this->byteUser,
                     'credential_1' => $this->bytePassword];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->byteUrl.'LOGIN');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // cookie config
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/bytecookie');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/bytecookie');

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        curl_exec($ch);
        $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($returnCode !== 200) {
            throw new \Exception('Could not login to byte!');
        }

    }

    /**
     * Let the user select a DB
     *
     */
    public function getSelectedDb(OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');

        $domain = $dialog->ask(
            $output,
            '<question>For what domain would you like to get the available backups?</question>'
        );

        //get available DB's
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->byteUrl.'dbbackups/'.$domain.'/json/');
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/bytecookie');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/bytecookie');

        $result = curl_exec($ch);
        curl_close($ch);

        $databases = json_decode($result);
        var_dump($databases);
        var_dump(count($databases));

        if (count($databases) === 1) {
            $database = $databases[0]['database'];
        } else {
            //@TODO database selector
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/bytecookie');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/bytecookie');
        curl_setopt($ch, CURLOPT_URL, $this->byteUrl.'dbbackups/'.$domain.'/'.$database.'/json/');

        $result = curl_exec($ch);
        curl_close($ch);

        $backups = json_decode($result);

        $backup = $backups[0]['filename'];

    }

}

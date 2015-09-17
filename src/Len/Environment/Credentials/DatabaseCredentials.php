<?php
/**
 * Entity class holding database credentials.
 *
 * @package Len\Environment\Credentials
 */

namespace Len\Environment\Credentials;

use \N98\Magento\Application;
use \N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Helper\QuestionHelper;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Question\Question;

/**
 * Entity class holding database credentials.
 */
final class DatabaseCredentials
{
    /**
     * The default user name.
     *
     * @var string DEFAULT_USER
     */
    const DEFAULT_USER = 'root';

    /**
     * The default host name.
     *
     * @var string DEFAULT_HOST
     */
    const DEFAULT_HOST = 'localhost';

    /**
     * The default port.
     *
     * @var int DEFAULT_PORT
     */
    const DEFAULT_PORT = 3306;

    /**
     * The user which connects to the database.
     *
     * @var string $_user
     */
    protected $_user = 'root';

    /**
     * The password to authenticate the user.
     *
     * @var string $_password
     */
    protected $_password;

    /**
     * The port on which the server is listening.
     *
     * @var int $_port
     */
    protected $_port = 3306;

    /**
     * The database to access.
     *
     * @var string $_database
     */
    protected $_database;

    /**
     * The host on which the database is served.
     *
     * @var string $_host
     */
    protected $_host = 'localhost';

    /**
     * Setter for the _user property.
     *
     * @param string $user
     * @return DatabaseCredentials
     * @throws \InvalidArgumentException when $user is not of type string or
     *   empty.
     */
    private function setUser($user)
    {
        if (!is_string($user) || empty($user)) {
            throw new \InvalidArgumentException(
                'Invalid user supplied: '
                . var_export($user, true)
            );
        }

        $this->_user = $user;

        return $this;
    }

    /**
     * Setter for the _password property.
     *
     * @param string $password
     * @return DatabaseCredentials
     * @throws \InvalidArgumentException when $password is not of type string
     *   or empty.
     */
    private function setPassword($password)
    {
        if (!is_string($password) || empty($password)) {
            throw new \InvalidArgumentException(
                'Invalid password supplied: '
                . var_export($password, true)
            );
        }

        $this->_password = $password;

        return $this;
    }

    /**
     * Setter for the _port property.
     *
     * @param int $port
     * @return DatabaseCredentials
     * @throws \InvalidArgumentException when $port is not of type int or < 1.
     */
    private function setPort($port)
    {
        if (!is_int($port) || $port < 1) {
            throw new \InvalidArgumentException(
                'Invalid port supplied: '
                . var_export($port, true)
            );
        }

        $this->_port = $port;

        return $this;
    }

    /**
     * Setter for the _database property.
     *
     * @param string $database
     * @return DatabaseCredentials
     * @throws \InvalidArgumentException when $database is not of type string
     *   or empty.
     */
    private function setDatabase($database)
    {
        if (!is_string($database) || empty($database)) {
            throw new \InvalidArgumentException(
                'Invalid database supplied: '
                . var_export($database, true)
            );
        }

        $this->_database = $database;

        return $this;
    }

    /**
     * Setter for the _host property.
     *
     * @param string $host
     * @return DatabaseCredentials
     * @throws \InvalidArgumentException when $host is not of type string or
     *   empty.
     */
    private function setHost($host)
    {
        if (!is_string($host) || empty($host)) {
            throw new \InvalidArgumentException(
                'Invalid host supplied: '
                . var_export($host, true)
            );
        }

        $this->_host = $host;

        return $this;
    }

    /**
     * Getter for the _user property.
     *
     * @return string
     * @throws \LogicException when property _user is not set.
     */
    public function getUser()
    {
        if (!isset($this->_user)) {
            throw new \LogicException('Missing property _user');
        }
        return $this->_user;
    }

    /**
     * Getter for the _password property.
     *
     * @return string
     * @throws \LogicException when property _password is not set.
     */
    public function getPassword()
    {
        if (!isset($this->_password)) {
            throw new \LogicException('Missing property _password');
        }
        return $this->_password;
    }

    /**
     * Getter for the _port property.
     *
     * @return int
     * @throws \LogicException when property _port is not set.
     */
    public function getPort()
    {
        if (!isset($this->_port)) {
            throw new \LogicException('Missing property _port');
        }
        return $this->_port;
    }

    /**
     * Getter for the _database property.
     *
     * @return string
     * @throws \LogicException when property _database is not set.
     */
    public function getDatabase()
    {
        if (!isset($this->_database)) {
            throw new \LogicException('Missing property _database');
        }
        return $this->_database;
    }

    /**
     * Getter for the _host property.
     *
     * @return string
     * @throws \LogicException when property _host is not set.
     */
    public function getHost()
    {
        if (!isset($this->_host)) {
            throw new \LogicException('Missing property _host');
        }
        return $this->_host;
    }

    /**
     * Create database credentials using the supplied command and output.
     *
     * @param AbstractMagentoCommand $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return static
     */
    public static function fromCommand(
        AbstractMagentoCommand $command,
        InputInterface $input,
        OutputInterface $output
    )
    {
        return static::fromQuestionHelper(
            $command->getHelper('question'),
            $input,
            $output
        );
    }

    /**
     * Create password credentials for the given question helper, input and
     * output. Useful for Symfony commands.
     *
     * @param QuestionHelper $helper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return static
     */
    public static function fromQuestionHelper(
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $rv = new static;

        $output->writeln('<info>Requesting database credentials:</info>');

        $defaultHost = static::DEFAULT_HOST;
        $rv->setHost(
            $helper->ask(
                $input,
                $output,
                new Question(
                    "Host [{$defaultHost}]: ",
                    $defaultHost
                )
            )
        );

        $defaultPort = static::DEFAULT_PORT;
        $rv->setPort(
            (int) $helper->ask(
                $input,
                $output,
                new Question("Port [{$defaultPort}]: ", $defaultPort)
            )
        );

        $rv->setDatabase(
            $helper->ask($input, $output, new Question('Database: '))
        );

        $defaultUser = static::DEFAULT_USER;
        $rv->setUser(
            $helper->ask(
                $input,
                $output,
                new Question("User [{$defaultUser}]: ", $defaultUser)
            )
        );

        $passwordQuestion = new Question('Password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);

        $rv->setPassword(
            $helper->ask($input, $output, $passwordQuestion)
        );

        return $rv;
    }

    /**
     * Create database credentials from the supplied application, using the
     * corresponding local.xml file.
     *
     * @param Application $app
     * @return static
     * @throws \RuntimeException when the local.xml file does not exist or is
     *   not writable.
     */
    public static function fromApplication(Application $app)
    {
        $rv = new static();

        $root = $app->getMagentoRootFolder();
        $localXmlPath = realpath("{$root}/app/etc/local.xml");

        if (!file_exists($localXmlPath) || !is_writable($localXmlPath)) {
            throw new \RuntimeException(
                'File does not exist or could not be written to: '
                . var_export($localXmlPath, true)
            );
        }

        // Load the XML and travel down the xpath until we find our connection.
        $localXml = simplexml_load_file($localXmlPath);
        $connection = $localXml
            ->global
            ->resources
            ->default_setup
            ->connection;

        if (property_exists($connection, 'host')) {
            $rv->setHost((string) $connection->host);
        }

        if (property_exists($connection, 'port')) {
            $rv->setPort((int) $connection->port);
        }

        if (property_exists($connection, 'username')) {
            $rv->setUser((string) $connection->username);
        }

        if (property_exists($connection, 'password')) {
            $rv->setPassword((string) $connection->password);
        }

        if (property_exists($connection, 'dbname')) {
            $rv->setDatabase((string) $connection->dbname);
        }

        return $rv;
    }

    /**
     * Store the current database credentials to the default database
     * connection of the supplied application in the corresponding local.xml.
     *
     * @param Application $app
     * @return void
     * @throws \RuntimeException when the local.xml file does not exist or is
     *   not writable.
     */
    public function saveToApplication(Application $app)
    {
        $root = $app->getMagentoRootFolder();
        $localXmlPath = realpath("{$root}/app/etc/local.xml");

        if (!file_exists($localXmlPath) || !is_writable($localXmlPath)) {
            throw new \RuntimeException(
                'File does not exist or could not be written to: '
                . var_export($localXmlPath, true)
            );
        }

        // Load the XML and travel down the xpath until we find our connection.
        $localXml = simplexml_load_file($localXmlPath);

        // Update the connection settings.
        $localXml
            ->global
            ->resources
            ->default_setup
            ->connection->host = $this->getHost();

        $localXml
            ->global
            ->resources
            ->default_setup
            ->connection->port = $this->getPort();

        $localXml
            ->global
            ->resources
            ->default_setup
            ->connection->username = $this->getUser();

        $localXml
            ->global
            ->resources
            ->default_setup
            ->connection->password = $this->getPassword();

        $localXml
            ->global
            ->resources
            ->default_setup
            ->connection->dbname = $this->getDatabase();

        // And persist the changes.
        $localXml->asXML($localXmlPath);
    }

    /**
     * Expand the current credentials to the supplied binary path.
     *
     * @param string $clientBinaryPath
     * @param bool $selectDatabase
     * @return string
     * @throws \InvalidArgumentException when $clientBinaryPath is not a
     *   string or not a path to an executable.
     * @throws \InvalidArgumentException when $selectDatabase is not a boolean.
     */
    public function expandToClient($clientBinaryPath, $selectDatabase = true)
    {
        if (!is_string($clientBinaryPath)
            || !is_executable($clientBinaryPath)
        ) {
            throw new \InvalidArgumentException(
                'Invalid client binary path supplied: '
                . var_export($clientBinaryPath, true)
            );
        }

        if (!is_bool($selectDatabase)) {
            throw new \InvalidArgumentException(
                'Invalid flag selectDatabase: ' . gettype($selectDatabase)
            );
        }

        $host = escapeshellarg($this->getHost());
        $database = escapeshellarg($this->getDatabase());
        $user = escapeshellarg($this->getUser());
        $password = escapeshellarg($this->getPassword());

        return $clientBinaryPath
            . " --host={$host}"
            // No need to escape the port.
            . " --port={$this->getPort()}"
            . ($selectDatabase ? " --database={$database}" : '')
            . " --user={$user}"
            . " --password={$password}";
    }
}

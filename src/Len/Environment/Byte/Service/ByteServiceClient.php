<?php
/**
 * Service client for Byte hosting.
 *
 * @package Len\Environment\Byte\Service
 */

namespace Len\Environment\Byte\Service;

// Since N98 magerun does not support PSR-4 autoloading, we do this manually.
require_once realpath(__DIR__ . '/../../../../../') . '/guzzle.php';

use \DateTime;
use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\ClientException;
use \Len\Environment\Credentials\ByteCredentials;

/**
 * Service client for Byte hosting.
 */
class ByteServiceClient
{
    /**
     * Location of a byte auth file, to skip user password authentication for
     * the byte environment.
     *
     * @var string AUTH_FILE
     */
    const AUTH_FILE = '~/.byte-auth.json';

    /**
     * The endpoint for authentication with the Byte service.
     *
     * @var string AUTH_ENDPOINT
     */
    const AUTH_ENDPOINT = 'https://auth.byte.nl/login/';

    /**
     * The endpoint for service API interactions.
     *
     * @var string SERVICE_ENDPOINT
     */
    const SERVICE_ENDPOINT = 'https://service.byte.nl/';

    /**
     * The packet size to use while streaming downloads.
     *
     * @var int DOWNLOAD_PACKET_SIZE
     */
    const DOWNLOAD_PACKET_SIZE = 1024;

    /**
     * The credentials entity we use to authenticate with Byte.
     *
     * @var ByteCredentials $_credentials
     */
    private $_credentials;

    /**
     * The HTTP client.
     *
     * @var Client $_client
     */
    protected $_client;

    /**
     * A helper to extract backups and list meta information.
     *
     * @var BackupExtractor $_extractor
     */
    protected $_extractor;

    /**
     * A list of registrants that correspond to the current client.
     *
     * @var Registrant[] $_registrants
     */
    protected $_registrants;

    /**
     * Initialize a new byte client.
     *
     * @param ByteCredentials $_credentials
     */
    final public function __construct(ByteCredentials $_credentials)
    {
        $this->_credentials = $_credentials;
    }

    /**
     * Create a Byte service client from a local auth file.
     *
     * @return static
     * @throws \RuntimeException when the authentication file could not be read.
     */
    public static function fromAuthFile()
    {
        $file = static::AUTH_FILE;

        // Expand home paths.
        if (array_key_exists('HOME', $_SERVER)) {
            $file = preg_replace(
                '/^~/',
                $_SERVER['HOME'],
                $file
            );
        }

        $file = realpath($file);

        if (!is_readable($file)) {
            throw new \RuntimeException(
                'Cannot read authentication file: ' . static::AUTH_FILE
            );
        }

        return new static(
            ByteCredentials::fromAuthFile($file)
        );
    }

    /**
     * Getter for the _client property.
     *
     * @return Client
     */
    protected function getClient()
    {
        if (!isset($this->_client)) {
            $this->_client = $this->authenticateClient(
                new Client(
                    [
                        'base_uri' => static::SERVICE_ENDPOINT,
                        // Share a cookie jar.
                        'cookies' => true
                    ]
                ),
                $this->getCredentials()
            );
        }

        return $this->_client;
    }

    /**
     * Authenticate the supplied client with the supplied credentials.
     *
     * @param Client $client
     * @param ByteCredentials $credentials
     * @return Client
     * @throws \RuntimeException when the client could not authenticate.
     */
    protected function authenticateClient(
        Client $client,
        ByteCredentials $credentials
    )
    {
        // Fetch the CSRF middleware token and store it in the cookie jar.
        $csrfResponse = $client->get(
            'login',
            // Don't throw on the 403 response header we will encounter.
            ['http_errors' => false]
        );

        if (!preg_match(
            '/csrftoken\=([^\;]+)\;/',
            $csrfResponse->getHeaderLine('Set-Cookie'),
            $matches
        )) {
            throw new \RuntimeException(
                'Did not receive a CSRF token in the cookie headers: '
                . $csrfResponse->getHeaderLine('Set-Cookie')
            );
        }

        list(, $token) = $matches;

        // Log in to byte.
        $client->post(
            // Do not forget the trailing slash, since Byte does a 301 and
            // loses all form data otherwise.
            // Note that we are explicitly using a different domain here.
            static::AUTH_ENDPOINT,
            [
                'form_params' => [
                    'csrfmiddlewaretoken' => $token,
                    'username' => $credentials->getUser(),
                    'password' => $credentials->getPassword()
                ],
                'headers' => [
                    // This is a "security" requirement of Byte.
                    'Referer' => static::AUTH_ENDPOINT
                ]
            ]
        );

        return $client;
    }

    /**
     * Getter for the _credentials property.
     *
     * @return ByteCredentials
     * @throws \LogicException when property _credentials is not set.
     */
    private function getCredentials()
    {
        if (!isset($this->_credentials)) {
            throw new \LogicException('Missing property _credentials');
        }

        return $this->_credentials;
    }

    /**
     * Getter for the _extractor property.
     *
     * @return BackupExtractor
     */
    protected function getExtractor()
    {
        if (!isset($this->_extractor)) {
            $this->_extractor = new BackupExtractor();
        }

        return $this->_extractor;
    }

    /**
     * Get the main registrant based on the authentication credentials.
     *
     * @return Registrant
     * @throws \OutOfBoundsException when the main registrant could not be
     *   found in the list of available registrants.
     */
    public function getMainRegistrant()
    {
        $credentials = $this->getCredentials();
        $registrants = $this->getRegistrants();

        $registrantIndex = (int) $credentials->getUser();

        if (!array_key_exists($registrantIndex, $registrants)) {
            throw new \OutOfBoundsException(
                "Requested registrant index #{$registrantIndex} not found."
            );
        }

        return $registrants[$registrantIndex];
    }

    /**
     * Get a list of registrant names, keyed by their identifier.
     *
     * @return Registrant[]
     * @note Sadly this method is highly dependent on the HTML layout of
     *   their overview page and is prone to break without prior warning!
     */
    public function getRegistrants()
    {
        if (!isset($this->_registrants)) {
            $client = $this->getClient();

            $response = $client->get('protected/overzicht/');
            $responseBody = $response
                ->getBody()
                ->getContents();

            preg_match_all(
                '/toggleRegistrant\([\'\"](\d+)[\'\"]\)/',
                $responseBody,
                $registrantMatches
            );

            $registrants = array_map(
                'intval',
                next($registrantMatches)
            );

            // For added stability, we iterate over the body, once per
            // registrant, to try and prevent this feature from breaking by
            // having too complex and specific regular expressions.
            $this->_registrants = array_filter(
                array_combine(
                    $registrants,
                    array_map(
                        function ($registrant) use ($responseBody) {
                            $anchorMatch = preg_match(
                                '/\<a[^\>]+toggleRegistrant\(.'
                                . $registrant . '.\)[^\>]+\>.*'
                                . '\&nbsp\;([^\<]+)'
                                . '.*\<\/a\>/',
                                $responseBody,
                                $matches
                            );

                            if (!$anchorMatch) {
                                return false;
                            }

                            return new Registrant(
                                $registrant,
                                trim(next($matches))
                            );
                        },
                        $registrants
                    )
                )
            );
        }

        return $this->_registrants;
    }

    /**
     * Get a list of domains for the supplied registrant.
     *
     * @param Registrant $registrant
     * @return array
     */
    public function getDomains(Registrant $registrant)
    {
        $response = $this
            ->getClient()
            ->post(
                'protected/overzicht/domains.cgi',
                [
                    'form_params' => [
                        'json' => json_encode(
                            ['regid' => $registrant->getId()]
                        )
                    ]
                ]
            );

        preg_match_all(
            '/\<td\>\<a[^\>]+\>([^\>]+)\<\/a\>\<\/td\>/',
            $response
                ->getBody()
                ->getContents(),
            $matches
        );

        $rv = array();

        if (!empty($matches[1])) {
            $rv = $matches[1];
        }

        return $rv;
    }

    /**
     * Get a list of databases for the supplied domain.
     *
     * @param string $domain
     * @return array
     * @throws \InvalidArgumentException when $domain is not a string.
     */
    public function getDatabases($domain)
    {
        if (!is_string($domain)) {
            throw new \InvalidArgumentException(
                'Domain must be a string: ' . gettype($domain)
            );
        }

        $client = $this->getClient();

        try {
            $response = $client->get("dbbackups/{$domain}/json/");
        } catch (ClientException $e) {
            $response = false;
        }

        return array_filter(
            array_map(
                function ($entry) {
                    return property_exists($entry, 'database')
                        ? $entry->database
                        : false;
                },
                json_decode(
                    ($response
                        ? $response
                            ->getBody()
                            ->getContents()
                        : '[]')
                )
            )
        );
    }

    /**
     * Get a list of database backups for the given domain and database.
     *
     * @param string $domain
     * @param string $database
     * @return DatabaseBackup[]
     * @throws \InvalidArgumentException when $domain is not a string.
     * @throws \InvalidArgumentException when $database is not a string.
     */
    public function getDatabaseBackups($domain, $database)
    {
        if (!is_string($domain)) {
            throw new \InvalidArgumentException(
                'Domain must be a string: ' . gettype($domain)
            );
        }

        if (!is_string($database)) {
            throw new \InvalidArgumentException(
                'Database must be a string: ' . gettype($database)
            );
        }

        $client = $this->getClient();

        try {
            $response = $client->get(
                "dbbackups/{$domain}/{$database}/json/"
            );
        } catch (ClientException $e) {
            $response = false;
        }

        return array_filter(
            array_map(
                function ($entry) use ($domain, $database) {
                    if (!property_exists($entry, 'size_bytes')
                        || !property_exists($entry, 'creation_utc_datetime')
                        || !property_exists($entry, 'filename')
                    ) {
                        return false;
                    }

                    return new DatabaseBackup(
                        $domain,
                        $database,
                        $entry->filename,
                        (int) $entry->size_bytes,
                        new DateTime(
                            $entry->creation_utc_datetime
                        )
                    );
                },
                json_decode(
                    ($response
                        ? $response
                            ->getBody()
                            ->getContents()
                        : '[]')
                )
            )
        );
    }

    /**
     * Download the supplied backup to temporary file location.
     * Returns whether the download succeeded fully.
     *
     * @param DatabaseBackup $backup
     * @return bool
     */
    public function downloadBackup(DatabaseBackup $backup)
    {
        $client = $this->getClient();
        $domain = $backup->getDomain();
        $database = $backup->getDatabase();
        $fileName = $backup->getFileName();
        $destination = $backup->getTempFileName();

        $response = $client->get(
            "dbbackups/{$domain}/{$database}/{$fileName}",
            ['stream' => true]
        );

        $body = $response->getBody();
        $output = fopen($destination, 'w');

        while (!$body->eof()) {
            fwrite(
                $output,
                $body->read(static::DOWNLOAD_PACKET_SIZE)
            );
        }

        fclose($output);

        return $backup->getSize() === filesize($destination);
    }

    /**
     * Get a list of files inside the backup archive.
     *
     * @param DatabaseBackup $backup
     * @return array
     */
    public function listBackupFiles(DatabaseBackup $backup)
    {
        return $this
            ->getExtractor()
            ->exposeInternalFiles($backup);
    }

    /**
     * Extract the supplied backup.
     *
     * @param DatabaseBackup $backup
     * @return void
     */
    public function extractBackup(DatabaseBackup $backup)
    {
        $this
            ->getExtractor()
            ->extract($backup);
    }
}

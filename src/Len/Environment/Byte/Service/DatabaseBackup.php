<?php
/**
 * Entity class holding information about a database backup.
 *
 * @package Len\Environment\Byte\Service
 */

namespace Len\Environment\Byte\Service;

use \DateTime;

/**
 * Entity class holding information about a database backup.
 */
class DatabaseBackup
{
    /**
     * The domain for which this backup is.
     *
     * @var string $_domain
     */
    protected $_domain;

    /**
     * The database for which this backup is.
     *
     * @var string $_database
     */
    protected $_database;

    /**
     * The file name of the backup.
     *
     * @var string $_fileName
     */
    protected $_fileName;

    /**
     * The size of the backup in bytes.
     *
     * @var int $_size
     */
    protected $_size;

    /**
     * A DateTime instance reflecting the date at which the backup has been
     * created.
     *
     * @var DateTime $_date
     */
    protected $_date;

    /**
     * Initialize a new database backup entity.
     *
     * @param string $domain
     * @param string $database
     * @param string $fileName
     * @param int $size
     * @param DateTime $date
     */
    public function __construct(
        $domain,
        $database,
        $fileName,
        $size,
        DateTime $date
    )
    {
        $this->setDomain($domain);
        $this->setDatabase($database);
        $this->setFileName($fileName);
        $this->setSize($size);
        $this->_date = $date;
    }

    /**
     * Setter for the _domain property.
     *
     * @param string $domain
     * @return DatabaseBackup
     * @throws \InvalidArgumentException when $domain is not of type string.
     */
    private function setDomain($domain)
    {
        if (!is_string($domain)) {
            throw new \InvalidArgumentException(
                'Invalid domain supplied: ' . var_export($domain, true)
            );
        }

        $this->_domain = $domain;

        return $this;
    }

    /**
     * Setter for the _database property.
     *
     * @param string $database
     * @return DatabaseBackup
     * @throws \InvalidArgumentException when $database is not of type string.
     */
    private function setDatabase($database)
    {
        if (!is_string($database)) {
            throw new \InvalidArgumentException(
                'Invalid database supplied: ' . var_export($database, true)
            );
        }

        $this->_database = $database;

        return $this;
    }

    /**
     * Setter for the _fileName property.
     *
     * @param string $fileName
     * @return DatabaseBackup
     * @throws \InvalidArgumentException when $fileName is not of type string.
     */
    private function setFileName($fileName)
    {
        if (!is_string($fileName)) {
            throw new \InvalidArgumentException(
                'Invalid fileName supplied: ' . var_export($fileName, true)
            );
        }

        $this->_fileName = $fileName;

        return $this;
    }

    /**
     * Setter for the _size property.
     *
     * @param int $size
     * @return DatabaseBackup
     * @throws \InvalidArgumentException when $size is not of type int or <= 0.
     */
    private function setSize($size)
    {
        if (!is_int($size) || $size <= 0) {
            throw new \InvalidArgumentException(
                'Invalid size supplied: ' . var_export($size, true)
            );
        }
        $this->_size = $size;
        return $this;
    }

    /**
     * Getter for the _domain property.
     *
     * @return string
     * @throws \LogicException when property _domain is not set.
     */
    public function getDomain()
    {
        if (!isset($this->_domain)) {
            throw new \LogicException('Missing property _domain');
        }
        return $this->_domain;
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
     * Getter for the _fileName property.
     *
     * @return string
     * @throws \LogicException when property _fileName is not set.
     */
    public function getFileName()
    {
        if (!isset($this->_fileName)) {
            throw new \LogicException('Missing property _fileName');
        }
        return $this->_fileName;
    }

    /**
     * Getter for the _size property.
     *
     * @return int
     * @throws \LogicException when property _size is not set.
     */
    public function getSize()
    {
        if (!isset($this->_size)) {
            throw new \LogicException('Missing property _size');
        }
        return $this->_size;
    }

    /**
     * Getter for the _date property.
     *
     * @return DateTime
     * @throws \LogicException when property _date is not set.
     */
    public function getDate()
    {
        if (!isset($this->_date)) {
            throw new \LogicException('Missing property _date');
        }
        return $this->_date;
    }

    /**
     * Return the file name inside the system temporary folder.
     *
     * @return string
     */
    public function getTempFileName()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getFileName();
    }
}

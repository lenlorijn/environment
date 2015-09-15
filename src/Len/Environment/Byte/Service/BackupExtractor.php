<?php
/**
 * Backup extractor.
 *
 * @package Len\Environment\Byte\Service
 */

namespace Len\Environment\Byte\Service;

/**
 * Backup extractor.
 */
class BackupExtractor
{
    /**
     * The system name of the binary.
     *
     * @var string BINARY
     */
    const BINARY = 'gunzip';

    /**
     * The full path to the archive extractor binary.
     *
     * @var string $_binary
     */
    private $_binary;

    /**
     * Getter for the _binary property.
     *
     * @return string
     * @throws \RuntimeException when the extractor binary is not installed.
     */
    protected function getBinary()
    {
        if (!isset($this->_binary)) {
            $binary = escapeshellarg(static::BINARY);
            $binary = trim(shell_exec("which {$binary}"));

            if (empty($binary)) {
                throw new \RuntimeException(
                    'Missing local installation of: ' . static::BINARY
                );
            }

            $this->_binary = $binary;
        }

        return $this->_binary;
    }

    /**
     * Return a list of files inside the backup archive.
     *
     * @param DatabaseBackup $backup
     * @return array
     * @throws \RuntimeException if the archive for the given backup does not
     *   exist or is not readable.
     */
    public function exposeInternalFiles(DatabaseBackup $backup)
    {
        $archive = $backup->getTempFileName();

        if (!is_readable($archive)) {
            throw new \RuntimeException(
                "Archive does not exist or is not readable: {$archive}"
            );
        }

        exec(
            "{$this->getBinary()} -l {$archive}",
            $files
        );

        // The first line is not a file, but a table head.
        array_shift($files);

        // We only require the filename, so look for that.
        return array_map(
            function ($line) {
                return array_pop(
                    preg_split('/\%\s/', $line)
                );
            },
            $files
        );
    }

    /**
     * Extract the supplied backup.
     *
     * @param DatabaseBackup $backup
     * @return void
     * @throws \RuntimeException if the archive for the given backup does not
     *   exist or is not readable.
     */
    public function extract(DatabaseBackup $backup)
    {
        $archive = $backup->getTempFileName();

        if (!is_readable($archive)) {
            throw new \RuntimeException(
                "Archive does not exist or is not readable: {$archive}"
            );
        }

        exec("{$this->getBinary()} -fk {$archive}");
    }
}

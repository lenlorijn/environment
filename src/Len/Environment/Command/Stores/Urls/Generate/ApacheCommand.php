<?php
/**
 * Command to generate Apache vhost entries.
 *
 * @package Len\Environment\Command\Stores\Urls\Generate
 */

namespace Len\Environment\Command\Stores\Urls\Generate;

use \Len\Environment\Command\Stores\Urls\HostNameFetcher;
use \N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to generate Apache vhost entries.
 */
class ApacheCommand extends AbstractMagentoCommand
{
    /**
     * Configure the command.
     * Sets command name and description.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('len:stores:generate:apache');
        $this->setDescription(
            'Generate ServerName and ServerAlias entries for your Apache vhost'
        );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \RuntimeException when Magento could not be initialized
     * @throws \RuntimeException when Magento could not deliver a proper core
     *   resource entity.
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        if (!$this->initMagento()) {
            throw new \RuntimeException('Could not initialize Magento!');
        }

        static $fetcher;

        if (!isset($fetcher)) {
            $fetcher = new HostNameFetcher();
        }

        $hostNames = array_values(
            $fetcher->getByConfig(
                \Mage::getConfig()
            )
        );

        foreach ($hostNames as $i => $hostName) {
            $directive = $i === 0
                ? 'ServerName'
                : 'ServerAlias';

            $output->writeln(
                "\t{$directive} {$hostName}"
            );
            $output->writeln(
                "\tServerAlias www.{$hostName}"
            );
        }
    }
}

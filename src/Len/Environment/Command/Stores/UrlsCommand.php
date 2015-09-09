<?php
/**
 * Magerun command that shows the domains of the local magento installation.
 *
 * @package Len\Environment\Command\Stores
 */

namespace Len\Environment\Command\Stores;

use \Len\Environment\Command\Stores\Urls\HostNameFetcher;
use \N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Magerun command that shows the domains of the local magento installation.
 */
class UrlsCommand extends AbstractMagentoCommand
{
    /**
     * Configure the command.
     * Sets command name and description.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('len:stores:urls');
        $this->setDescription(
            'Shows a list of all store base URLs of the current project'
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

        $hostNames = $fetcher->getByConfig(
            \Mage::getConfig()
        );

        foreach ($hostNames as $hostName) {
            $output->writeln($hostName);
        }
    }
}

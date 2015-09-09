<?php
/**
 * Command to generate entries for /etc/hosts.
 *
 * @package Len\Environment\Command\Stores\Urls\Generate
 */

namespace Len\Environment\Command\Stores\Urls\Generate;

use \Len\Environment\Command\Stores\Urls\HostNameFetcher;
use \N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to generate entries for /etc/hosts.
 */
class HostsCommand extends AbstractMagentoCommand
{
    /**
     * Configure the command.
     * Sets command name and description.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('len:stores:generate:hosts');
        $this->setDescription('Generate entries for /etc/hosts');
        $this->addArgument(
            'server',
            InputArgument::OPTIONAL,
            'The host name / IP of the server for which the entries are',
            'localhost'
        );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \InvalidArgumentException when the supplied server argument
     *   could not be resolved to an IP address.
     * @throws \RuntimeException when Magento could not be initialized
     * @throws \RuntimeException when Magento could not deliver a proper core
     *   resource entity.
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getArgument('server');
        $ipAddress = gethostbyname($host);

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException(
                "Could not resolve hostname to an IP: {$host} => {$ipAddress}"
            );
        }

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
            $output->writeln(
                "{$ipAddress}\t{$hostName} www.{$hostName}"
            );
        }
    }
}

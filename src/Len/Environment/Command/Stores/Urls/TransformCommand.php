<?php
/**
 * Command to transform base URLs from one environment to another.
 * E.g.: production => staging, production => dev, staging => dev.
 * Note: This transformation is one-way directional.
 *
 * @package Len\Environment\Command\Stores\Urls
 */

namespace Len\Environment\Command\Stores\Urls;

use \N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to transform base URLs from one environment to another.
 */
class TransformCommand extends AbstractMagentoCommand
{
    /**
     * Configure the command.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('len:stores:urls:transform');
        $this->setDescription(
            'Transform URL structure from production => staging => dev'
        );
        $this->addArgument(
            'environment',
            InputArgument::REQUIRED,
            'The environment to transform to: staging, dev'
        );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \InvalidArgumentException when the environment argument is not
     *   one of staging or dev.
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getArgument('environment');

        static $allowedDestinations = array('staging', 'dev');

        if (!in_array($destination, $allowedDestinations, true)) {
            throw new \InvalidArgumentException(
                'Destination environment must be one of: '
                . implode(', ', $allowedDestinations)
                . "; Yet received: {$destination}"
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
    }
}

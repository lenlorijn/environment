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

        $this->addOption(
            'project',
            'p',
            InputArgument::OPTIONAL,
            'The name of the project. Defaults to the current directory.',
            basename(getcwd())
        );
        $this->addOption(
            'environment',
            'e',
            InputArgument::OPTIONAL,
            'The environment to transform to: staging, dev',
            UrlTransformer::DEFAULT_ENVIRONMENT
        );
        $this->addOption(
            'domain',
            'd',
            InputArgument::OPTIONAL,
            'The domain to use as top level',
            UrlTransformer::DEFAULT_DOMAIN
        );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \RuntimeException when Magento could not be initialized.
     * @throws \InvalidArgumentException when the environment argument is not
     *   one of staging or dev.
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        if (!$this->initMagento()) {
            throw new \RuntimeException('Could not initialize Magento!');
        }

        $transformer = new UrlTransformer(\Mage::app());

        try {
            $transformer->setEnvironment(
                $input->getOption('environment')
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                "Invalid environment supplied: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }

        $transformer->setProject($input->getOption('project'));
        $transformer->setDomain($input->getOption('domain'));

        if ($output->getVerbosity() === $output::VERBOSITY_VERBOSE) {
            $output->writeln(
                "<info>Domain:</info> {$transformer->getDomain()}"
            );
            $output->writeln(
                "<info>Project:</info> {$transformer->getProject()}"
            );
            $output->writeln(
                "<info>Environment:</info> {$transformer->getEnvironment()}"
            );
            $output->writeln(
                "<info>Default domain:</info> {$transformer->createDomain()}"
            );
        }

        $this->processStores($transformer, $output);

        // Clean the config cache.
        \Mage::getConfig()->cleanCache();
    }

    /**
     * Process the stores for the supplied mage application.
     *
     * @param UrlTransformer $transformer
     * @param OutputInterface $output
     * @return void
     */
    protected function processStores(
        UrlTransformer $transformer,
        OutputInterface $output
    )
    {
        foreach ($transformer->getApplicableStores() as $store) {
            $this->processStore(
                $transformer,
                $store,
                $output
            );
        }
    }

    /**
     * Process the given store.
     *
     * @param UrlTransformer $transformer
     * @param \Mage_Core_Model_Store $store
     * @param OutputInterface $output
     * @return void
     */
    protected function processStore(
        UrlTransformer $transformer,
        \Mage_Core_Model_Store $store,
        OutputInterface $output
    )
    {
        if ($output->getVerbosity() === $output::VERBOSITY_VERBOSE) {
            $output->writeln(
                "<comment>{$store->getCode()}</comment> "
                . "<info>{$store->getName()}</info> "
                . "- {$store->getFrontendName()}"
            );
        }

        $output->writeln(
            $transformer->createDomain($store)
        );

        $rewrites = $transformer->rewriteStore($store);

        if ($output->getVerbosity() === $output::VERBOSITY_VERBOSE) {
            foreach ($rewrites as $xpath => $baseUrl) {
                $output->writeln("\t<info>{$xpath}</info> => {$baseUrl}");
            }
        }
    }
}

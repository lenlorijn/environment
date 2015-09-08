<?php
/**
 * Magerun command that shows the domains of the local magento installation.
 *
 * @package Len\Environment\Command\Stores
 */
namespace Len\Environment\Command\Stores;

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

        $resource = \Mage::getSingleTon('core/resource');

        if (!($resource instanceof \Mage_Core_Model_Resource)) {
            throw new \RuntimeException(
                'Unexpected resource entity: ' . get_class($resource)
            );
        }

        $readConnection = $resource->getConnection('core_read');

        $select = $readConnection
            ->select()
            ->from(
                array('c' => 'core_config_data'),
                array('url' => 'c.value')
            )
            ->where(
                'c.path IN(?)',
                array(
                    'web/unsecure/base_url',
                    'web/secure/base_url'
                )
            );

        // Fetch all unique host names.
        $hostNames = array_unique(
            // Filter out empty entries.
            array_filter(
                array_map(
                    // Strip off the 'www.' subdomain.
                    function (array $row) {
                        return preg_replace(
                            '/^www\./i',
                            '',
                            parse_url(
                                $row['url'],
                                PHP_URL_HOST
                            )
                        );
                    },
                    // Fetch all rows for the given Select instance.
                    $readConnection->fetchAll($select)
                )
            )
        );

        sort($hostNames);

        foreach ($hostNames as $hostName) {
            $output->writeln($hostName);
        }
    }
}

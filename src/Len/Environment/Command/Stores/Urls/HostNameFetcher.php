<?php
/**
 * Helper entity to fetch host names of a Magento installation for a given
 * database resource or connection.
 *
 * @package Len\Environment\Command\Stores\Urls
 */

namespace Len\Environment\Command\Stores\Urls;

/**
 * Helper entity to fetch host names of a Magento installation for a given
 * database resource or connection.
 */
class HostNameFetcher
{
    /**
     * Get a list of host names for the supplied database adapter.
     *
     * @param \Varien_Db_Adapter_Interface $db
     * @return array
     */
    public function getByConnection(\Varien_Db_Adapter_Interface $db)
    {
        return $this->filterHostNames(
            array_map(
                function (array $row) {
                    return $row['url'];
                },
                $db->fetchAll(
                    $this->createSelect($db)
                )
            )
        );
    }

    /**
     * Create a select query for the supplied database adapter.
     *
     * @param \Varien_Db_Adapter_Interface $db
     * @return \Varien_Db_Select
     */
    protected function createSelect(\Varien_Db_Adapter_Interface $db)
    {
        return $db
            ->select()
            ->from(
                array('c' => 'core_config_data'),
                array('url' => 'DISTINCT(c.value)')
            )
            ->where(
                'c.path IN(?)',
                array(
                    'web/unsecure/base_url',
                    'web/secure/base_url'
                )
            );
    }

    /**
     * Filter the supplied list of host names, make them unique and sort them.
     *
     * @param array $hostNames
     * @return array
     */
    protected function filterHostNames(array $hostNames)
    {
        // Fetch all unique host names.
        $rv = array_unique(
            // Filter out empty entries.
            array_filter(
                array_map(
                    // Strip off the 'www.' sub-domain.
                    function ($url) {
                        return preg_replace(
                            '/^www\./i',
                            '',
                            parse_url(
                                $url,
                                PHP_URL_HOST
                            )
                        );
                    },
                    $hostNames
                )
            )
        );

        sort($rv);

        return $rv;
    }

    /**
     * Get a list of host names for the supplied database resource.
     *
     * @param \Mage_Core_Model_Resource $resource
     * @return array
     */
    public function getByResource(\Mage_Core_Model_Resource $resource)
    {
        return $this->getByConnection(
            $resource->getConnection('core_read')
        );
    }

    /**
     * Get a list of host names for the supplied magento configuration.
     *
     * @param \Mage_Core_Model_Config $config
     * @return array
     */
    public function getByConfig(\Mage_Core_Model_Config $config)
    {
        return $this->filterHostNames(
            array_merge(
                $config->getStoresConfigByPath(
                    'web/unsecure/base_url'
                ),
                $config->getStoresConfigByPath(
                    'web/secure/base_url'
                )
            )
        );
    }
}

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
        return $this->fetchHostNames(
            $db,
            $this->createSelect($db)
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
     * Fetch host names for the supplied database adapter and select query.
     *
     * @param \Varien_Db_Adapter_Interface $db
     * @param \Varien_Db_Select $select
     * @return array
     */
    protected function fetchHostNames(
        \Varien_Db_Adapter_Interface $db,
        \Varien_Db_Select $select
    )
    {
        // Fetch all unique host names.
        $rv = array_unique(
            // Filter out empty entries.
            array_filter(
                array_map(
                    // Strip off the 'www.' sub-domain.
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
                    $db->fetchAll($select)
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
}

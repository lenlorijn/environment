<?php
/**
 * Helper class to transform the URLs of a given Mage Application to suit the
 * required environment.
 *
 * @package Len\Environment\Command\Stores\Urls
 */

namespace Len\Environment\Command\Stores\Urls;

/**
 * Helper class to transform the URLs of a given Mage Application to suit the
 * required environment.
 */
class UrlTransformer
{
    /**
     * The default environment.
     *
     * @var string DEFAULT_ENVIRONMENT
     */
    const DEFAULT_ENVIRONMENT = 'dev';

    /**
     * The default domain.
     *
     * @var string DEFAULT_DOMAIN
     */
    const DEFAULT_DOMAIN = 'mediacthq.nl';

    /**
     * The mage application for which we will update stores.
     *
     * @var \Mage_Core_Model_App $_application
     */
    private $_application;

    /**
     * The project name.
     *
     * @var string $_project
     */
    private $_project;

    /**
     * The base domain name.
     *
     * @var string $_domain
     */
    private $_domain;

    /**
     * The environment to transform toward.
     *
     * @var string $_environment
     */
    private $_environment;

    /**
     * Initialize a new URL transformer.
     *
     * @param \Mage_Core_Model_App $application
     */
    public function __construct(\Mage_Core_Model_App $application)
    {
        $this->_application = $application;
    }

    /**
     * Getter for the _application property.
     *
     * @return \Mage_Core_Model_App
     * @throws \LogicException when property _application is not set.
     */
    protected function getApplication()
    {
        if (!isset($this->_application)) {
            throw new \LogicException('Missing property _application');
        }
        return $this->_application;
    }

    /**
     * Getter for the project property.
     *
     * @return string
     * @throws \LogicException when property project is not set.
     */
    public function getProject()
    {
        if (!isset($this->_project)) {
            throw new \LogicException('Missing property project');
        }

        return $this->_project;
    }

    /**
     * Setter for the project property.
     *
     * @param string $project
     * @return TransformCommand
     * @throws \InvalidArgumentException when $project is not of type string.
     */
    public function setProject($project)
    {
        if (!is_string($project)) {
            throw new \InvalidArgumentException(
                'Invalid project supplied: '
                . var_export($project, true)
            );
        }

        $this->_project = $this->cleanDomainPart($project);

        return $this;
    }

    /**
     * Getter for the domain property.
     *
     * @return string
     * @throws \LogicException when property domain is not set.
     */
    public function getDomain()
    {
        if (!isset($this->_domain)) {
            throw new \LogicException('Missing property domain');
        }
        return $this->_domain;
    }

    /**
     * Setter for the domain property.
     *
     * @param string $domain
     * @return TransformCommand
     * @throws \InvalidArgumentException when $domain is not of type string.
     */
    public function setDomain($domain)
    {
        if (!is_string($domain)) {
            throw new \InvalidArgumentException(
                'Invalid domain supplied: '
                . var_export($domain, true)
            );
        }

        $this->_domain = $this->cleanDomainPart($domain);

        return $this;
    }

    /**
     * Getter for the environment property.
     *
     * @return string
     * @throws \LogicException when property environment is not set.
     */
    public function getEnvironment()
    {
        if (!isset($this->_environment)) {
            throw new \LogicException('Missing property environment');
        }
        return $this->_environment;
    }

    /**
     * Setter for the environment property.
     *
     * @param string $environment
     * @return TransformCommand
     * @throws \InvalidArgumentException when $environment is not one of
     *   'dev' or 'staging'.
     */
    public function setEnvironment($environment)
    {
        static $allowedEnvironments = array('staging', 'dev');

        if (!in_array($environment, $allowedEnvironments, true)) {
            throw new \InvalidArgumentException(
                'Destination environment must be one of: '
                . implode(', ', $allowedEnvironments)
                . "; Yet received: {$environment}"
            );
        }

        $this->_environment = $environment;

        return $this;
    }

    /**
     * Clean the given domain part by stripping off invalid characters and
     * trimming off surrounding domain delimiters (.).
     *
     * @param string $part
     * @return string
     */
    protected function cleanDomainPart($part)
    {
        return trim(
            preg_replace(
                '/[^a-z0-9\-\_\.]/i',
                '',
                $part
            ),
            '.'
        );
    }

    /**
     * Create a domain from a given store, or a default domain if none is given.
     *
     * @param null|\Mage_Core_Model_Store $store
     * @return string
     */
    public function createDomain(\Mage_Core_Model_Store $store = null)
    {
        $rv = implode(
            '.',
            [$this->getProject(), $this->getEnvironment(), $this->getDomain()]
        );

        if (isset($store) && $store->isAdmin() === false) {
            $storeCode = $store->getCode();

            if ($storeCode !== $store::DEFAULT_CODE) {
                $rv = "{$storeCode}-{$rv}";
            }
        }

        return $rv;
    }

    /**
     * Transform a given store into a new base url.
     *
     * @param \Mage_Core_Model_Store $store
     * @param bool $secure
     * @return string
     * @throws \InvalidArgumentException when $secure is not a boolean
     */
    public function transformBaseUrl(
        \Mage_Core_Model_Store $store,
        $secure = false
    )
    {
        if (!is_bool($secure)) {
            throw new \InvalidArgumentException(
                'Invalid value for $secure supplied. Expected a boolean. Got: '
                . gettype($secure)
            );
        }

        $currentUrl = $store->getBaseUrl($store::URL_TYPE_LINK, $secure);
        $path = parse_url($currentUrl, PHP_URL_PATH);
        $scheme = parse_url($currentUrl, PHP_URL_SCHEME);
        $domain = $this->createDomain($store);

        return "{$scheme}://{$domain}{$path}";
    }

    /**
     * Get a list of rewrites for the store base URLs.
     *
     * @param \Mage_Core_Model_Store $store
     * @return array
     */
    public function getStoreRewrites(\Mage_Core_Model_Store $store)
    {
        return [
            'web/unsecure/base_url' => $this->transformBaseUrl($store),
            'web/secure/base_url' => $this->transformBaseUrl($store, true)
        ];
    }

    /**
     * Rewrite the store base urls and return the used rewrites
     *
     * @param \Mage_Core_Model_Store $store
     * @return array
     */
    public function rewriteStore(\Mage_Core_Model_Store $store)
    {
        $rewrites = $this->getStoreRewrites($store);

        foreach ($rewrites as $xpath => $baseUrl) {
            $this->saveStoreConfig($store, $xpath, $baseUrl);
        }

        return $rewrites;
    }

    /**
     * Save the supplied config for the supplied store.
     *
     * @param \Mage_Core_Model_Store $store
     * @param string $xpath
     * @param string $value
     * @return void
     */
    protected function saveStoreConfig(
        \Mage_Core_Model_Store $store,
        $xpath,
        $value
    )
    {
        $config = $this
            ->getApplication()
            ->getConfig();

        $config->saveConfig($xpath, $value, 'stores', $store->getId());
    }

    /**
     * Get the store entities that are applicable for transformation.
     * Sort them as such that the default store is on top of the list.
     *
     * @return \Mage_Core_Model_Store[]
     */
    public function getApplicableStores()
    {
        /** @var \Mage_Core_Model_Store[] $stores */
        $stores = $this
            ->getApplication()
            ->getStores(true);

        // Ensure the default store goes first.
        usort(
            $stores,
            function (\Mage_Core_Model_Store $store) {
                return $store->getCode() === $store::DEFAULT_CODE
                    ? -1
                    : 1;
            }
        );

        return $stores;
    }
}

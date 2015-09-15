<?php

namespace Len\Environment\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;

class DbCommand extends AbstractMagentoCommand
{

    /**
    * Configure the command
    *
    * @return void
    */
    public function configure(){
        $this->setName('len:config:db')
          ->setDescription('Configures DB for dev environment');
    }

    /**
    * Execute config command
    *
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    *
    * @return int|void
    */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->initMagento();

        //Getting the magento DB connection to write our new configs
        $resource = \Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        $output->writeln("<info>Updating Base URLs to {{base_url}}</info>");

        $writeConnection->query("UPDATE core_config_data SET value='{{base_url}}' WHERE path='web/unsecure/base_url'");
        $writeConnection->query("UPDATE core_config_data SET value='{{base_url}}' WHERE path='web/secure/base_url'");

        // Remove unwanted logs from tables
        $output->writeln("<info>Trunkating log and report tables</info>");

        $writeConnection->query("TRUNCATE log_url");
        $writeConnection->query("TRUNCATE log_url_info");
        $writeConnection->query("TRUNCATE log_visitor");
        $writeConnection->query("TRUNCATE log_visitor_info");
        $writeConnection->query("TRUNCATE report_event");

        // Dissable JS and CSS merging
        $output->writeln("<info>Disabeling merged JS and CSS</info>");

        $writeConnection->query("UPDATE core_config_data SET value='0' WHERE path='dev/css/merge_css_files' OR path='dev/js/merge_files'");
        $writeConnection->query("TRUNCATE report_event");

        // Dissable analitics @todo make Mct_Seo compatible
        $output->writeln("<info>Dissabeling analytics</info>");
        $writeConnection->query("UPDATE core_config_data SET value='0' WHERE path='google/analytics/active'");

        // set email adresses to fakes
        $output->writeln("<info>Changing email adresses</info>");

        $writeConnection->query("UPDATE core_config_data SET value='contact-magento-dev@trash-mail.com' WHERE path='trans_email/ident_general/email'");
        $writeConnection->query("UPDATE core_config_data SET value='contact-magento-dev@trash-mail.com' WHERE path='trans_email/ident_sales/email'");
        $writeConnection->query("UPDATE core_config_data SET value='contact-magento-dev@trash-mail.com' WHERE path='trans_email/ident_support/email'");
        $writeConnection->query("UPDATE core_config_data SET value='contact-magento-dev@trash-mail.com' WHERE path='trans_email/ident_custom1/email'");
        $writeConnection->query("UPDATE core_config_data SET value='contact-magento-dev@trash-mail.com' WHERE path='trans_email/ident_custom2/email'");

        // Up increment ID to avoid payment method problems
        $output->writeln("<info>Up the increment ID's of orders to avoid conflicts with external systems</info>");

        $writeConnection->query("UPDATE eav_entity_store SET increment_last_id=10*increment_last_id");

        // Shotgun all settings that probably need to be to test
        $output->writeln("<info>Set multiple modules in test mode</info>");

        $writeConnection->query("UPDATE core_config_data SET value='test' WHERE value LIKE 'live'");
        $writeConnection->query("UPDATE core_config_data SET value='test' WHERE value LIKE 'prod'");
        $writeConnection->query("UPDATE core_config_data SET value=1 WHERE path LIKE '%/testmode'");

        $writeConnection->query("UPDATE core_config_data SET value='127.0.0.1' WHERE path LIKE '%/allow_ips'");
        $writeConnection->query("UPDATE core_config_data SET value='127.0.0.1' WHERE path LIKE 'etipsecurity/%/allow'");
        $writeConnection->query("UPDATE core_config_data SET value=1 WHERE path LIKE 'dev/log/active'");
        $writeConnection->query("UPDATE core_config_data SET value=0 WHERE path LIKE 'dev/debug/template_hints'");
        $writeConnection->query("UPDATE core_config_data SET value=0 WHERE path LIKE 'dev/js/merge_files'");
        $writeConnection->query("UPDATE core_config_data SET value=0 WHERE path LIKE 'dev/css/merge_css_files'");

        $output->writeln("<info>Setting allow symlink to true</info>");
        $writeConnection->query("UPDATE core_config_data SET value='1' WHERE path='dev/template/allow_symlink'");

        $output->writeln("<info>Flushing cache to apply changes in config</info>");
        $this->flushCache($output);

    }

    /**
     * Flushes the magento cache for good measure
     */
    protected function flushCache(OutputInterface $output)
    {
        $input = new StringInput('cache:flush');

        // ensure that n98-magerun doesn't stop after first command
        $this->getApplication()->setAutoExit(false);

        // flush cache
        $this->getApplication()->run($input, $output);

        // reactivate auto-exit
        $this->getApplication()->setAutoExit(true);

    }

}

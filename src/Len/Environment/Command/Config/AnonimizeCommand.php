<?php

namespace Len\Environment\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;

class AnonimizeCommand extends AbstractMagentoCommand {

  /**
   * Configure the command
   */
  public function configure(){
    $this->setName('len:config:anonimize')
      ->setDescription('Anonimizes the database');
  }

    /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    public function execute(InputInterface $input, OutputInterface $output)
    {
      $this->detectMagento($output, true);
      $this->initMagento();

      //Getting the magento DB connection to write our new configs
      $resource = \Mage::getSingleton('core/resource');
      $writeConnection = $resource->getConnection('core_write');

      $output->writeln("<info></info>");

      // customer address
      $output->writeln("<info>Clearing customer adresses</info>");
            
      $ENTITY_TYPE="customer_address";
      $ATTR_CODE="firstname";

      $writeConnection->query("UPDATE customer_address_entity_varchar SET value=CONCAT('firstname_',entity_id) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      $ATTR_CODE="lastname";
      $writeConnection->query("UPDATE customer_address_entity_varchar SET value=CONCAT('lastname_',entity_id) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      $ATTR_CODE="telephone";
      $writeConnection->query("UPDATE customer_address_entity_varchar SET value=CONCAT('0341 12345',entity_id) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      $ATTR_CODE="fax";
      $writeConnection->query("UPDATE customer_address_entity_varchar SET value=CONCAT('0171 12345',entity_id) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      $ATTR_CODE="street";
      $writeConnection->query("UPDATE customer_address_entity_text SET value=CONCAT(entity_id,' test avenue') WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      // customer account data
      $output->writeln("<info>Clearing other customer account data</info>");

      $ENTITY_TYPE="customer";
      $writeConnection->query("UPDATE customer_entity SET email=CONCAT('dev_',entity_id,'@trash-mail.com')");

      $ATTR_CODE="firstname";
      $writeConnection->query("UPDATE customer_entity_varchar SET value=CONCAT('firstname_',entity_id) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      $ATTR_CODE="lastname";
      $writeConnection->query("UPDATE customer_entity_varchar SET value=CONCAT('lastname_',entity_id) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      $ATTR_CODE="password_hash";
      $writeConnection->query("UPDATE customer_entity_varchar SET value=MD5(CONCAT('dev_',entity_id,'@trash-mail.com')) WHERE attribute_id=(select attribute_id from eav_attribute where attribute_code='$ATTR_CODE' and entity_type_id=(select entity_type_id from eav_entity_type where entity_type_code='$ENTITY_TYPE'))");

      // credit memo
      $output->writeln("<info>Clear credit memo info</info>");

      $writeConnection->query("UPDATE sales_flat_creditmemo_grid SET billing_name='Demo User'");

      // invoices
      $output->writeln("<info>Clear invoices</info>");
      
      $writeConnection->query("UPDATE sales_flat_invoice_grid SET billing_name='Demo User'");

      // shipments
      $output->writeln("<info>Clear shipments</info>");

      $writeConnection->query("UPDATE sales_flat_shipment_grid SET shipping_name='Demo User'");

      // quotes
      $output->writeln("<info>Clear all quote data</info>");

      $writeConnection->query("UPDATE sales_flat_quote SET customer_email=CONCAT('dev_',entity_id,'@trash-mail.com'), customer_firstname='Demo', customer_lastname='User', customer_middlename='Dev', remote_ip='192.168.1.1', password_hash=NULL");
      $writeConnection->query("UPDATE sales_flat_quote_address SET firstname='Demo', lastname='User', company=NULL, telephone=CONCAT('0123-4567', address_id), street=CONCAT('Devstreet ',address_id)");

      // orders
      $output->writeln("<info>Clear order data</info>");

      $writeConnection->query("UPDATE sales_flat_order SET customer_email=CONCAT('dev_',entity_id,'@trash-mail.com'), customer_firstname='Demo', customer_lastname='User', customer_middlename='Dev'");
      $writeConnection->query("UPDATE sales_flat_order_address SET firstname='Demo', lastname='User', company=NULL, telephone=CONCAT('0123-4567', entity_id), street=CONCAT('Devstreet ',entity_id)");
      $writeConnection->query("UPDATE sales_flat_order_grid SET shipping_name='Demo D. User', billing_name='Demo D. User'");

      // payments
      $output->writeln("<info>Clear payment info</info>");
      $writeConnection->query("UPDATE sales_flat_order_payment SET additional_data=NULL, additional_information=NULL");

      // newsletter
      $output->writeln("<info>Clear newsletter subscribers</info>");

      $writeConnection->query("UPDATE newsletter_subscriber SET subscriber_email=CONCAT('dev_newsletter_',subscriber_id,'@trash-mail.com')");


      $this->flushCache($output);

    }

    /**
     * Flushes cache for good measure
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

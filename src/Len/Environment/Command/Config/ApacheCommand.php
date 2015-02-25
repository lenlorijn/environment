<?php

namespace Len\Environment\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ApacheCommand extends AbstractMagentoCommand {

  /**
   * Configure the command
   */
  public function configure(){
    $this->setName('len:config:apache')
      ->addArgument('projectname', InputArgument::REQUIRED, "The name of the project")
      ->setDescription('Generates and installs apache config files');
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

    $storeCodes = $this->getStoreCodes();

    $projectname = $input->getArgument('projectname');
    $templateVariables = array('project_name' => $projectname, 'stores' => $storeCodes );

    $virtualhostTemplate = file_get_contents(__DIR__.'/templates/apache/virtualhost.conf.twig');

    $apacheConfig = $this->getHelper('twig')->renderString($virtualhostTemplate, $templateVariables);

    file_put_contents('/etc/apache2/sites-available/' . $projectname . '.conf', $apacheConfig);
    
    $output->writeln("<info>Enabeling site and restarting apache</info>");

    $this->applyChanges($projectname);
  }

  /**
   * restarts apache process after enabeling new site
   */
  protected function applyChanges($projectname)
  {
    exec('a2ensite '.$projectname);
    exec('apachectl -k graceful');
  }

  protected function getStoreCodes()
  {
    $stores = array();

    foreach (\Mage::app()->getStores() as $store) {
        $stores[]=$store->getCode();
    }

    return $stores;
  }

}

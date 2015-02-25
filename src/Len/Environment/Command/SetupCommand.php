<?php

namespace Len\Environment\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;



class SetupCommand extends AbstractMagentoCommand {

  /**
   * Configure the command
   */
  public function configure(){
    $this->setName('len:setup')
      ->addArgument('projectname', InputArgument::REQUIRED, "The name of the project")
      ->setDescription('Set up a new dev env');
  }

    /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

      $projectname = $input->getArgument('projectname');

      $output->writeln("<info>Making dir /var/www/".$projectname." and going there</info>");

      $output->writeln("<info>Cloning repo: git@mediact.git.beanstalkapp.com:/mediact/".$projectname.".git </info>");
      passthru('git clone git@mediact.git.beanstalkapp.com:/mediact/'.$projectname.'.git '.$projectname);

      $output->writeln("<info>initialising git git flow and attempting to fetch repo</info>");
      chdir('/var/www/'.$projectname);
      passthru('git flow init');

      $output->writeln("<info>Initialising modman</info>");
      passthru('modman init');

      $output->writeln("<info>Cloning standard dev tools in to repo</info>");
      passthru('modman clone https://github.com/AOEpeople/Aoe_Profiler.git');
      passthru('modman clone https://github.com/AOEpeople/Aoe_TemplateHints.git');
      passthru('modman clone git@mediact.git.beanstalkapp.com:/mediact/commercebug.git');

      $output->writeln("<info>Going to make Apache config</info>");
      passthru('sudo n98-magerun len:config:apache '.$projectname);

      //run other setup commands
      //$input = new StringInput('len:config:apache '.$projectname);

      // ensure that n98-magerun doesn't stop after first command
      //$this->getApplication()->setAutoExit(false);
     
      // with output
      //$this->getApplication()->run($input, $output);
      
      // reactivate auto-exit
      //$this->getApplication()->setAutoExit(true);
    }
}

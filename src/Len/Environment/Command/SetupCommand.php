<?php

namespace Len\Environment\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;



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
      mkdir('/var/www/'.$projectname);
      chdir('/var/www/'.$projectname);

      $output->writeln("<info>initialising git git flow and attempting to fetch repo</info>");
      passthru('git init');
      passthru('git flow init');

      $output->writeln("<info>Adding repo: git@mediact.git.beanstalkapp.com:/mediact/".$projectname.".git </info>");
      passthru('git remote add origin git@mediact.git.beanstalkapp.com:/mediact/'.$projectname.'.git');
      passthru('git pull');

      $output->writeln("<info>Initialising modman</info>");
      passthru('modman init');

      $output->writeln("<info>Cloning standard dev tools in to repo</info>");
      passthru('modman clone https://github.com/AOEpeople/Aoe_Profiler.git');
      passthru('modman clone https://github.com/AOEpeople/Aoe_TemplateHints.git');
      passthru('modman clone git@mediact.git.beanstalkapp.com:/mediact/commercebug.git');

    }

}

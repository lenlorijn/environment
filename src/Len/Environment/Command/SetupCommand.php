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
     * @return void
     */
    public function configure(){
        $this->setName('len:setup')
            ->addArgument('projectname', InputArgument::REQUIRED, "The name of the project")
            ->setDescription('Set up a new dev env');
    }

    /**
     * Execute Setup Command
     *
    * @param \Symfony\Component\Console\Input\InputInterface   $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    *
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Inputs to use in script
        $projectname = $input->getArgument('projectname');

        // Make commands to use in the process of setting up
        $gitCommand = new StringInput('len:setup:git '.$projectname);
        $modmanCommand = new StringInput('len:setup:modman');

        // Ensure that n98-magerun doesn't stop after other commands
        $this->getApplication()->setAutoExit(false);

        // Creating place for project to live
        $output->writeln("<info>Making dir /var/www/".$projectname." and going there</info>");
        mkdir('/var/www/'.$projectname);
        chdir('/var/www/'.$projectname);

        // Run other commands
        $this->getApplication()->run($gitCommand, $output);
        $this->getApplication()->run($modmanCommand, $output);
        
        // Apache needs to be done externally as sudo
        $output->writeln("<info>Going to make Apache config</info>");
        passthru('sudo n98-magerun len:config:apache '.$projectname);

        // reactivate auto-exit
        $this->getApplication()->setAutoExit(true);
    }
}

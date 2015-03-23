<?php

namespace Len\Environment\Command\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;

/**
 * Git Command takes care of cloning and initialising git for the project
 *
 * PHP Version 5.5
 *
 * @category Setup
 * @package  Len_Environment
 * @author   Len Lorijn <lenlorijn@gmail.com>
 * @license  DBAD http://www.dbad-license.org/
 * @link     https://github.com/lenlorijn/environment
 */
class GitCommand extends AbstractMagentoCommand 
{
    /**
     * Configure the command
     * @return void
     */
    public function configure()
    {
        $this->setName('len:setup:git')
            ->addArgument('projectname', InputArgument::REQUIRED, "The name of the project")
            ->setDescription('Takes care of cloning and initalising git for the project');
    }

    /**
    * Ececutes the git command
    *
    * @param \Symfony\Component\Console\Input\InputInterface   $input 
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    *
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $projectname = $input->getArgument('projectname');

        // maybe move to setup command?

        $output->writeln("<info>Cloning repo: git@mediact.git.beanstalkapp.com:/mediact/".$projectname.".git </info>");
        passthru('git clone git@mediact.git.beanstalkapp.com:/mediact/'.$projectname.'.git .');

        $output->writeln("<info>initialising git git flow and attempting to fetch repo</info>");
        passthru('git flow init');

        $output->writeln("<info>Initialising modman</info>");
        passthru('modman init');

        $output->writeln("<info>Cloning standard dev tools in to repo</info>");
        passthru('modman clone https://github.com/AOEpeople/Aoe_Profiler.git');
        passthru('modman clone https://github.com/AOEpeople/Aoe_TemplateHints.git');
        passthru('modman clone git@mediact.git.beanstalkapp.com:/mediact/commercebug.git');
    }

}

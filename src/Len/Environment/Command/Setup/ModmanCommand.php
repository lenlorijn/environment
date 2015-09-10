<?php

namespace Len\Environment\Command\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;

/**
 * Modman command initialises modman and installs necessary dev modules
 *
 * PHP Version 5.5
 *
 * @category Setup
 * @package  Len_Environment
 * @author   Len Lorijn <lenlorijn@gmail.com>
 * @license  DBAD http://www.dbad-license.org/
 * @link     https://github.com/lenlorijn/environment
 */

class ModmanCommand extends AbstractMagentoCommand 
{
    /**
     * Configure the command
     * @return void
     */
    public function configure()
    {
        $this->setName('len:setup:modman')
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
        // Should become modman command
        $output->writeln("<info>Initialising modman</info>");
        passthru('modman init');

        $output->writeln("<info>Cloning standard dev tools in to repo</info>");
        passthru('modman clone https://github.com/AOEpeople/Aoe_Profiler.git');
        passthru('modman clone https://github.com/AOEpeople/Aoe_TemplateHints.git');
        passthru('modman clone https://github.com/AOEpeople/Aoe_Scheduler.git');
        passthru('modman clone git@mediact.git.beanstalkapp.com:/mediact/commercebug.git');
    }
}

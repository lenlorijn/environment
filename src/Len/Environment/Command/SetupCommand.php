<?php

namespace Len\Environment\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends AbstractMagentoCommand {

  /**
   * Configure the command
   */
  public function configure(){
    $this->setName('setup')
      ->setDescription('Set up a new dev env');
  }

    /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      //@todo  implement some awesome sutf that will set stuff up

    }

}

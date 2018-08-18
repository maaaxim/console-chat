<?php
/**
 * Created by PhpStorm.
 * User: maxim
 * Date: 8/18/18
 * Time: 2:59 PM
 */

namespace Maaaxim\ConsoleChat\Command;

use Maaaxim\ConsoleChat\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class InitUserCommand
 * @package Maaaxim\ConsoleChat\Command
 */
class InitUserCommand extends Command
{
    /**
     * Configure
     */
    public function configure()
    {
        $this->setName('init-user')
            ->setDescription("This console run command")
            ->addArgument('fromPort', InputArgument::REQUIRED . 'Your port')
            ->addArgument('toPort', InputArgument::REQUIRED . 'Your friend\'s port')
            ->addArgument('fromAddress', InputArgument::OPTIONAL . 'Your IP address', "", "127.0.0.1")
            ->addArgument('toAddress', InputArgument::OPTIONAL . 'Your friend\'s IP address', "", "127.0.0.1");
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fromPort = $input->getArgument('fromPort');
        $toPort = $input->getArgument('toPort');
        $fromAddress = $input->getArgument('fromAddress');
        $toAddress = $input->getArgument('toAddress');

        $user = new User($fromPort, $toPort, $fromAddress, $toAddress);
        $user->init();
    }
}
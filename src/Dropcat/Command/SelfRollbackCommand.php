<?php
namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfRollbackCommand extends DropcatCommand
{
    protected function configure()
    {
        $this
            ->setName('self:rollback')
            ->setAliases(["self-rollback"])
            ->setDescription('Rollbacks dropcat.phar to the last version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = new Updater(null, false);
        try {
            $result = $updater->rollback();
            if ($result) {
                $output->writeln("<info>Succesfully roll-backed version.</info>");

                return 0;
            } else {
                $output->writeln("<info>Roll-back failed.</info>");
            }
        } catch (\Exception $e) {
            $msg = "Something went wrong, sorry." . $e->getMessage();
            $output->writeln("<error>$msg</error>");
        }

        return 1;
    }
}

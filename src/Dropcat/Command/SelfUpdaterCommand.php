<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Humbug\SelfUpdate\Updater;
use Dropcat\Services\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @codeCoverageIgnore
 */
class SelfUpdaterCommand extends DropcatCommand
{
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates dropcat.phar to the latest version if needed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urlToPhar = 'https://dropcat.org/dropcat.phar';
        $urlToVersionFile = 'https://dropcat.org/dropcat.phar.version';
        $updater = new Updater(null, false);
        $updater->getStrategy()->setPharUrl($urlToPhar);
        $updater->getStrategy()->setVersionUrl($urlToVersionFile);
        try {
            $result = $updater->update();
            if (! $result) {
                $output = new ConsoleOutput();
                $output->writeln('<info>You are already a fashion lion. No update is needed.</info>');
                exit;
            }
            $new = $updater->getNewVersion();
            $old = $updater->getOldVersion();
            $output = new ConsoleOutput();
            $output->writeln("<info>Oh, fresh! Updated from $old to $new.</info>");
            exit;
        } catch (\Exception $e) {
            $output = new ConsoleOutput();
            $output->writeln("<info>We got an error. Sorry. $e->getMessage()</info>");
            exit;
        }
    }
}

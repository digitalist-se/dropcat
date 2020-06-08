<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
                $output->writeln('<info>You are already a fashion lion. No update is needed.</info>');
                return 0;
            }
            $new = $updater->getNewVersion();
            $old = $updater->getOldVersion();
            $output->writeln("<info>Oh, fresh! Updated from $old to $new.</info>");
            return 0;
        } catch (\Exception $e) {
            $output->writeln("<info>We got an error. Sorry. $e->getMessage()</info>");
            return 1;
        }
    }
}

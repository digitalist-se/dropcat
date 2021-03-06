<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitWordpressCommand extends DropcatCommand
{

    protected function configure()
    {
        $HelpText = '<error>deprecated, use dropcat init:wp instead</error>';

        $this->setName("init:wp")
            ->setDescription("Init WP site")
            ->setHelp($HelpText);
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->confirm('This will add files for setting up a WordPress site in current folder, continue?', true);
        // (startdir is needed for application)
        $process = new Process("git clone git@gitlab.wklive.net:bobodrone/bedrock.git web_init");
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();
        $io->note('Wk WordPress Template cloned to web_init/web');
        $process = new Process("shopt -s dotglob && mv web_init/* . && rm -rf web_init");
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();
        $io->note('Move web folder in place, removed web_init folder');
        $io->newLine(2);
        $io->success('Site is setup');
    }
}

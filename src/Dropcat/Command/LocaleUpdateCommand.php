<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class LocaleUpdateCommand extends DropcatCommand {


    protected static $defaultName = 'locale:update';

    protected function configure() {
        $HelpText = 'The <info>%command.name%</info> updates locales.';

        $this->setName("locale:update")
          ->setDescription("update locales")
          ->setDefinition(
            [
              new InputOption(
                'drush_alias',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Drush alias',
                $this->configuration->siteEnvironmentDrushAlias()
              ),
            ]
          )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $drush_alias = $input->getOption('drush_alias');
        if ($output->isVerbose()) {
            $out = 'Using drush alias: @' . $drush_alias . "\n";
            $output->writeln("<comment>$out</comment>");
        }
        $this->localeImport($drush_alias, $input, $output);
        $output->writeln('<info>locale:update finished</info>');
        return 0;
    }

    public function localeImport($drush_alias, $input, $output) {
        $cmd = ['drush', "@$drush_alias", 'locale:update'];
        $process = new Process($cmd);
        if ($output->isVerbose()) {
            $out = 'Running command: ' . $process->getCommandLine() . "\n";
            $output->writeln("<comment>$out</comment>");
        }
        $process->mustRun();

        while($process->isRunning() && $output->isVerbose()) {
            $output->writeln($process->getIncrementalOutput());
            $output->writeln($process->getOutput());
        }
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            $msg = $process->getErrorOutput();
            if (empty($msg)) {
                $msg = $process->getOutput();
            }
            $output->writeln("<error>Error: $msg</error>");
        }
        return $process->getExitCode();
    }
}

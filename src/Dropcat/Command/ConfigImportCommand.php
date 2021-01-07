<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConfigImportCommand extends DropcatCommand
{

    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command import configuration to drupal site.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat configimport</info>
To override config in dropcat.yml, using options:
<info>dropcat configimport -d mysite -c myconfig</info>';

        $this->setName("config-import")
          ->setDescription("Configuration import")
        ->setDefinition(
            array(
                new InputOption(
                    'drush_alias',
                    'd',
                    InputOption::VALUE_OPTIONAL,
                    'Drush alias',
                    $this->configuration->getFullDrushAlias()
                ),
                new InputOption(
                    'config_name',
                    'c',
                    InputOption::VALUE_OPTIONAL,
                    'Name of config to import',
                    $this->configuration->siteEnvironmentConfigName()
                ),
                new InputOption(
                    'timeout',
                    'to',
                    InputOption::VALUE_OPTIONAL,
                    'Timeout',
                    $this->configuration->timeOut()
                ),
              )
        )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drush_alias = $input->getOption('drush_alias');
        $config_name = $input->getOption('config_name');
        $timeout = $input->getOption('timeout');

        if ($output->isVerbose()) {
            $output->writeln('using drush alias: ' . $drush_alias);
            $output->writeln('using config: ' . $config_name);
        }

        $processCommand = ['drush', "@$drush_alias", 'cim', "$config_name", '-y'];

        if ($output->isVerbose()) {
            $processCommand = array_merge($processCommand, ['-v']);
        } else {
            $processCommand = array_merge($processCommand, ['-q']);
        }

        $process = $this->runProcess($processCommand);

        $process->setTimeout($timeout);
        $process->mustRun();
        // Executes after the command finishes.
        echo $process->getOutput();

        $output->writeln('<info>Task: configimport finished</info>');

        return 0;
    }
}

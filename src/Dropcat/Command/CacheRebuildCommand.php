<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CacheRebuildCommand extends DropcatCommand {

    protected function configure() {
        $HelpText = 'The <info>%command.name%</info> re-creates cache on a drupal site.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat cache-recreate</info>
To override config in dropcat.yml, using options:
<info>dropcat cache-rebuild -d mysite</info>';

        $this->setName("cache:rebuild")
          ->setDescription("rebuilds the cache")
          ->setAliases(['cache-recreate', 'cache:recreate', 'cr'])
          ->setDefinition(
            [
              new InputOption(
                'drush_alias',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Drush alias',
                $this->configuration->getFullDrushAlias()
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
        $cr = ['drush', "@$drush_alias", 'cache:rebuild'];
        $process = new Process($cr);
        if ($output->isVerbose()) {
            $out = 'Running command: ' . $process->getCommandLine() . "\n";
            $output->writeln("<comment>$out</comment>");
        }

        $process->run();

        // Executes after the command finishes.
        $stdOut = $process->getOutput();
        if (!empty($stdOut)) {
            $output->writeln("<comment>$stdOut</comment>");
        }

        $errorOutput = $process->getErrorOutput();
        if (!$process->isSuccessful() || !empty($errorOutput)) {
            $output->writeln("<error>Error: $errorOutput</error>");
            $output->writeln('<error>cache:rebuild failed</error>');

            return 1;
        }

        $output->writeln('<info>cache:rebuild finished</info>');

        return 0;
    }
}

<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Exception;

class DbImportCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command will import.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat dbimport</info>
To override config in dropcat.yml, using options:
<info>dropcat dbimport -d mysite -i /var/dump -t 120</info>';

        $this->setName("db:import")
            ->setAliases(["db-import"])
            ->setDescription("import database")
            ->setDefinition(
                array(
                    new InputOption(
                        'drush_alias',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Drush alias to import db from',
                        $this->configuration->siteEnvironmentDrushAlias()
                    ),
                    new InputOption(
                        'db_import',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'DB backup path',
                        $this->configuration->localEnvironmentDbImport()
                    ),
                    new InputOption(
                        'time_out',
                        't',
                        InputOption::VALUE_OPTIONAL,
                        'Time out for task, default to ' . $this->configuration->timeOut(),
                        $this->configuration->timeOut()
                    ),
                )
            )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drush_alias      = $input->getOption('drush_alias');
        $path_to_db       = $input->getOption('db_import');
        $timeout          = $input->getOption('time_out');
        $appname          = $this->configuration->localEnvironmentAppName();
        $db_dump          = "/tmp/$appname-db.sql";

        try {
            if (!isset($drush_alias)) {
                throw new Exception('drush alias is needed');
            }
            if (!isset($path_to_db)) {
                throw new Exception('path to db is needed');
            }
        } catch (Exception $e) {
            $output->writeln('error:' . $e->getMessage());
            return 1;
        }

        $output->writeln('<comment>Using drush alias: ' . $drush_alias . '</comment>', OutputInterface::VERBOSITY_VERBOSE);

        // Remove '@' if the alias beginns with it.
        $drush_alias = preg_replace('/^@/', '', $drush_alias);

        $output->writeln('<info>' . $this->start . ' db-import started</info>');

        if (file_exists($path_to_db)) {
            $output->writeln("<comment>Db exists at $path_to_db</comment>", OutputInterface::VERBOSITY_VERBOSE);
            $file_type = pathinfo($path_to_db);
            switch ($file_type['extension']) {
                case "gz":
                    $output->writeln("<comment>Filetype is gz</comment>", OutputInterface::VERBOSITY_VERBOSE);
                    // Using bash redirection, needs fromShellCommandLine
                    $process = Process::fromShellCommandline(
                        "gunzip $path_to_db --force -c > $db_dump"
                    );
                    $process->setTimeout($timeout);
                    $process->mustRun();
                    $output->writeln('<comment>' . $process->getOutput()
                      . '</comment>');
                    $output->writeln("<comment>unzipped $path_to_db to $db_dump</comment>");
                    break;
                case "sql":
                    $output->writeln("<comment>Filetype is sql</comment>");
                    break;
                default: // Handle no file extension
                    throw new \LogicException('Only gzip (.gz) & .sql is supported.');
            }
        } else {
            throw new Exception("Database backup was not found at: $path_to_db");
        }
        $sqlDropProcess = new Process(['drush', "@$drush_alias", 'sql-drop', '-y']);
        $sqlDropProcess->setTimeout($timeout);
        $sqlDropProcess->mustRun();
        $stdOut = $sqlDropProcess->getOutput();
        if (!empty($stdOut)) {
            $output->writeln('<comment>SQL Drop StdOut:</comment>', OutputInterface::VERBOSITY_VERBOSE);
            $output->writeln('<comment>' . $stdOut . '</comment>', OutputInterface::VERBOSITY_VERBOSE);
        }
        $errOut = $sqlDropProcess->getErrorOutput();
        if (!empty($errOut)) {
            $output->writeln('<error>SQL Drop Error Output:</error>');
            $output->writeln('<error>' . $errOut . '</error>');
            throw new Exception('There was an error while dropping the database.');
        }

        $sqlImportProcess = Process::fromShellCommandline("drush @$drush_alias sql-cli < $db_dump");
        $sqlImportProcess->setTimeout($timeout);
        $sqlImportProcess->mustRun();
        $stdOut = $sqlImportProcess->getOutput();
        if (!empty($stdOut)) {
            $output->writeln('<comment>SQL Import StdOut:</comment>', OutputInterface::VERBOSITY_VERBOSE);
            $output->writeln('<comment>' . $stdOut . '</comment>', OutputInterface::VERBOSITY_VERBOSE);
        }
        $errOut = $sqlImportProcess->getErrorOutput();
        if (!empty($errOut)) {
            $output->writeln('<error>SQL Import Error Output:</error>');
            $output->writeln('<error>' . $errOut . '</error>');
            throw new Exception('There was an error while importing the database.');
        }

        $output->writeln('<info>' . $this->heart . ' db-import finished</info>');
        
        return 0;
    }
}

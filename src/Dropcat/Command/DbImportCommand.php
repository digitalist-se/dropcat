<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
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

        $this->setName("db-import")
            ->setDescription("import database")
            ->setDefinition(
                array(
                    new InputOption(
                        'drush_alias',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Drush alias',
                        $this->configuration->siteEnvironmentDrushAlias()
                    ),
                    new InputOption(
                        'db_import',
                        'i',
                        InputOption::VALUE_OPTIONAL,
                        'Backup path',
                        $this->configuration->localEnvironmentDbImport()
                    ),
                    new InputOption(
                        'time_out',
                        't',
                        InputOption::VALUE_OPTIONAL,
                        'Time out',
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
            $output->writeln( 'error:' . $e->getMessage());
            return 1;
        }

        // Remove '@' if the alias beginns with it.
        $drush_alias = preg_replace('/^@/', '', $drush_alias);

        $output->writeln('<info>' . $this->start . ' db-import started</info>');

        if (file_exists($path_to_db)) {
            if ($output->isVerbose()) {
                $output->writeln( "<comment>Db exists at $path_to_db</comment>");
            }
            $file_type = pathinfo($path_to_db);
            switch ($file_type['extension']) {
                case "gz":
                    if ($output->isVerbose()) {
                        $output->writeln( "<comment>Filetype is gz</comment>");
                    }
                    // Using bash redirection, needs fromShellCommandLine
                    $process = Process::fromShellCommandline(
                        "gunzip $path_to_db --force -c > $db_dump"
                    );
                    $process->setTimeout($timeout);
                    $process->mustRun();
                    $output->writeln('<comment>' . $process->getOutput()
                      . '</comment>');
                    $output->writeln("<comment>un-gzipped $path_to_db to $db_dump</comment>");
                    break;
                case "sql":
                    $output->writeln("<comment>Filetype is sql</comment>");
                    break;
                default: // Handle no file extension
                    $output->writeln("only gzip (.gz) & .sql is supported for now");
                    return 1;
            }
        } else {
            $output->writeln("Db does not exist at $path_to_db");
            return 1;
        }
        $sqlDropProcess = new Process(['drush', '@$drush_alias', 'sql-drop', '-y']);
        $sqlDropProcess->setTimeout($timeout);
        $sqlDropProcess->mustRun();
        if ($output->isVerbose()) {
            $output->writeln('<comment>' . $sqlDropProcess->getOutput() . '</comment>');
        }
        
        $sqlImportProcess = Process::fromShellCommandline("drush @$drush_alias sql-cli < $db_dump");
        $sqlImportProcess->setTimeout($timeout);
        $sqlImportProcess->mustRun();
        if ($output->isVerbose()) {
            $output->writeln('<comment>' . $sqlImportProcess->getOutput() . '</comment>');
        }
        
        $output->writeln('<info>' . $this->heart . ' db-import finished</info>');
        
        return 0;
    }
}

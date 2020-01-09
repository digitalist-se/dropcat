<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GetDatabaseBackupCommand extends DropcatCommand {

    protected function configure() {
        $HelpText = 'The <info>%command.name%</info> command will run script or command.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat %command.name%</info>';

        $this->setName("get:db-backup")
          ->setDescription("run command or script on local environment")
          ->setDefinition(
            [
              new InputOption(
                'remote_backup_path',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'Remote backup path',
                $this->configuration->localEnvironmentBackupPath()
              ),
              new InputOption(
                'remote_db_backup_name',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'Remote db backup name',
                $this->configuration->localEnvironmentBackupDbName()
              ),
              new InputOption(
                'remote_backup_server',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'Remote backup server',
                $this->configuration->localEnvironmentBackupServer()
              ),
              new InputOption(
                'remote_backup_server_user',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'User for backup server',
                $this->configuration->localEnvironmentBackupServerUser()
              ),
              new InputOption(
                'remote_backup_server_port',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'SSH Remote backup up server ssh port',
                $this->configuration->localEnvironmentBackupServerPort()
              ),
            ]
          )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $remote_backup_path = $input->getOption('remote_backup_path');
        $remote_db_backup_name = $input->getOption('remote_db_backup_name');
        $remote_backup_server = $input->getOption('remote_backup_server');
        $remote_backup_server_user = $input->getOption('remote_backup_server_user');
        $remote_backup_server_port = $input->getOption('remote_backup_server_port');

        $scp = [
          'scp',
          '-C',
          '-P',
          "$remote_backup_server_port",
          "$remote_backup_server_user@$remote_backup_server:$remote_backup_path/$remote_db_backup_name",
          '.',
        ];
        $process = new Process($scp);
        if ($output->isVerbose()) {
            $out = $process->getCommandLine();
            $output->writeln("<comment>Running command: $out</comment>");
        }
        $output->writeln('<info>Downloading ' . $remote_db_backup_name . '</info>');
        $process->setTimeout(9999);
        $process->run();
        if (!$process->isSuccessful()) {
            $out = $process->getErrorOutput();
            $output->writeln("<error>$out</error>");
            return $process->getExitCode();
        }
        $out = $process->getOutput();
        $output->writeln("<info>$out</info>");
        $output->writeln('<info>Task: ' . $remote_db_backup_name . ' copied to current path.</info>');

        return 0;
    }
}

<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GetDatabaseBackupCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command will run script or command.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat %command.name%</info>';

        $this->setName("get:db-backup")
        ->setDescription("run command or script on local environment")
        ->setDefinition(
            array(
            new InputOption(
                'remote_backup_path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Remote backup path',
                $this->configuration->localEnvironmentBackupPath()
            ),
            new InputOption(
                'remote_db_backup_name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Remote db backup name',
                $this->configuration->localEnvironmentBackupDbName()
            ),
            new InputOption(
                'remote_backup_server',
                null,
                InputOption::VALUE_OPTIONAL,
                'Remote backup server',
                $this->configuration->localEnvironmentBackupServer()
            ),
            new InputOption(
                'remote_backup_server_user',
                null,
                InputOption::VALUE_OPTIONAL,
                'User for backup server',
                $this->configuration->localEnvironmentBackupServerUser()
            ),
            new InputOption(
                'remote_backup_server_port',
                null,
                InputOption::VALUE_OPTIONAL,
                'SSH Remote backup up server ssh port',
                $this->configuration->localEnvironmentBackupServerPort()
            ),
            )
        )
        ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $remote_backup_path = $input->getOption('remote_backup_path');
        $remote_db_backup_name = $input->getOption('remote_db_backup_name');
        $remote_backup_server = $input->getOption('remote_backup_server');
        $remote_backup_server_user = $input->getOption('remote_backup_server_user');
        $remote_backup_server_port = $input->getOption('remote_backup_server_port');

        if ($output->isVerbose()) {
            echo "Running scp -C -P $remote_backup_server_port $remote_backup_server_user@$remote_backup_server:$remote_backup_path/$remote_db_backup_name .\n";
        }

        $process = new Process("scp -C -P $remote_backup_server_port $remote_backup_server_user@$remote_backup_server:$remote_backup_path/$remote_db_backup_name .");
        $output->writeln('<info>downloading ' . $remote_db_backup_name . '</info>');
        $process->setTimeout(9999);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        $output->writeln('<info>Task: ' . $remote_db_backup_name . ' copied to current path.</info>');
    }
}

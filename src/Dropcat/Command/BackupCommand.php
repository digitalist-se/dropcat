<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Backup a site db and files.
 */
class BackupCommand extends DropcatCommand {

    protected static $defaultName = 'backup';

    protected function configure() {
        $HelpText = 'The <info>%command.name%</info> command will create a backup' .
          'of sites database or/and the whole web site folder.';
        $this->setName("backup")
          ->addUsage('-b /backup/dir')
          ->setDescription("backup site")
          ->setDefinition(
            [
              new InputOption(
                'app-name',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'application name',
                $this->configuration->localEnvironmentAppName()
              ),
              new InputOption(
                'mysql-host',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'mysql host',
                $this->configuration->mysqlEnvironmentHost()
              ),
              new InputOption(
                'mysql-port',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'mysql port',
                $this->configuration->mysqlEnvironmentPort()
              ),
              new InputOption(
                'mysql-db',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'mysql db',
                $this->configuration->mysqlEnvironmentDataBase()
              ),
              new InputOption(
                'mysql-user',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'mysql user',
                $this->configuration->mysqlEnvironmentUser()
              ),
              new InputOption(
                'mysql-password',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'mysql password',
                $this->configuration->mysqlEnvironmentPassword()
              ),
              new InputOption(
                'backup-path',
                'b',
                InputOption::VALUE_OPTIONAL,
                'backup path',
                $this->configuration->siteEnvironmentBackupPath()
              ),
              new InputOption(
                'time-out',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'time out',
                $this->configuration->timeOut()
              ),
              new InputOption(
                'backup-site',
                NULL,
                InputOption::VALUE_NONE,
                'backup whole site'
              ),
              new InputOption(
                'no-db-backup',
                NULL,
                InputOption::VALUE_NONE,
                'no database backup',
                NULL
              ),
              new InputOption(
                'backup-name',
                NULL,
                InputOption::VALUE_OPTIONAL,
                'name of backup',
                NULL
              ),
              new InputOption(
                'server',
                's',
                InputOption::VALUE_OPTIONAL,
                'server',
                $this->configuration->remoteEnvironmentServerName()
              ),
              new InputOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'User (ssh)',
                $this->configuration->remoteEnvironmentSshUser()
              ),
              new InputOption(
                'ssh-port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'SSH port',
                $this->configuration->remoteEnvironmentSshPort()
              ),
              new InputOption(
                'ssh-identity',
                'i',
                InputOption::VALUE_OPTIONAL,
                'SSH Identity File',
                $this->configuration->remoteEnvironmentIdentifyFile()
              ),
              new InputOption(
                'web-root',
                'w',
                InputOption::VALUE_OPTIONAL,
                'web root',
                $this->configuration->remoteEnvironmentWebRoot()
              ),
              new InputOption(
                'alias',
                'a',
                InputOption::VALUE_OPTIONAL,
                'symlink alias',
                $this->configuration->remoteEnvironmentAlias()
              ),
            ]
          )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $app = $input->getOption('app-name');
        $mysql_host = $input->getOption('mysql-host');
        $mysql_port = $input->getOption('mysql-port');
        $mysql_db = $input->getOption('mysql-db');
        $mysql_user = $input->getOption('mysql-user');
        $mysql_password = $input->getOption('mysql-password');
        $backup_path = $input->getOption('backup-path');
        $timeout = $input->getOption('time-out');
        $backup_site = $input->getOption('backup-site');
        $no_db_backup = $input->getOption('no-db-backup');
        $backup_name = $input->getOption('backup-name');
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $ssh_port = $input->getOption('ssh-port');
        $identityFile = $input->getOption('ssh-identity');
        $web_root = $input->getOption('web-root');
        $alias = $input->getOption('alias');
        $timestamp = $this->configuration->timeStamp();

        if (!isset($backup_name)) {
            $backup_name = $timestamp;
        }
        $output->writeln("<info>$this->start backup started</info>");
        if ($no_db_backup != TRUE) {
            $mkdir = ['mkdir', '-p', "$backup_path/$app"];
            $mysqldump = "mysqldump --port=$mysql_port -u $mysql_user " .
              "-p$mysql_password -h $mysql_host $mysql_db > $backup_path/$app/$backup_name.sql";
            $mkdirProcess = new Process($mkdir);
            $mkdirProcess->run();
            if ($mkdirProcess->isSuccessful()) {
                // Because we're using shell redirection we need to use fromShellCommand.
                $backupDb = Process::fromShellCommandline($mysqldump);
                if ($output->isVerbose()) {
                    $output->writeln("<comment>$this->cat Executing: {$backupDb->getCommandLine()}</comment>");
                }
                $backupDb->setTimeout($timeout);
                try {
                    $backupDb->mustRun();
                    if ($output->isVerbose()) {
                        $output->writeln("<comment>$this->cat Wrote backup to $backup_path/$app/$backup_name.sql</comment>");
                    }
                } catch (ProcessFailedException $e) {
                    $out = $backupDb->getErrorOutput();
                    $output->writeln("<error>$out</error>");
                    return $backupDb->getExitCode();
                }
            }
            else {
                $msg = $mkdirProcess->getErrorOutput();
                $output->writeln("<error>Could not create backup directory: $msg</error>");
                return $mkdirProcess->getExitCode();
            }
            $output->writeln("<info>$this->mark db backup done</info>");
        }
        if ($backup_site === TRUE) {
            $mkdir = ['mkdir', '-p', "$backup_path/$app"];
            $mkdirProcess = new Process($mkdir);
            $mkdirProcess->run();
            if ($mkdirProcess->isSuccessful()) {
                $sshCommand = "ssh -p $ssh_port";
                if (isset($sshCommand)) {
                    $sshCommand .= " -i $identityFile";
                }
                if ($output->isVerbose()) {
                    $sshCommand .= ' -o LogLevel=VERBOSE';
                    $rsyncOptions = '-vPaL';
                }
                else {
                    $sshCommand .= ' -o LogLevel=ERROR';
                    $rsyncOptions = '-qPaL';
                }
                $rsyncCommand = "rsync $rsyncOptions -e \"$sshCommand\" $user@$server:$web_root/$alias $backup_path/$app";
                $rsyncProcess = Process::fromShellCommandline($rsyncCommand);
                $rsyncProcess->setTimeout($timeout);
                if ($output->isVerbose()) {
                    $output->writeln("<comment>$this->cat Executing: {$rsyncProcess->getCommandLine()}</comment>");
                }
                try {
                    $rsyncProcess->mustRun();
                    if ($output->isVerbose()) {
                        $output->writeln("<comment>$this->cat Backed up site to $backup_path/$app</comment>");
                        $out = $rsyncProcess->getOutput();
                        $output->writeln("<comment>$this->cat $out</comment>");
                    }
                } catch (ProcessFailedException $e) {
                    $out = $rsyncProcess->getErrorOutput();
                    $output->writeln("<error>$out</error>");
                    return $rsyncProcess->getExitCode();
                }
            }
            else {
                $msg = $mkdirProcess->getErrorOutput();
                $output->writeln("<error>Could not create backup directory: $msg</error>");
                return $mkdirProcess->getExitCode();
            }
            $output->writeln("<info>$this->mark site backup done</info>");
        }
        $output->writeln("<info>$this->heart backup finished</info>");

        return 0;
    }
}

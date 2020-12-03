<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Backup a site db and files over ssh.
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

    /**
     * @param SSH2 $ssh
     * @param string $cmd
     * @param OutputInterface $output
     * @return bool|string
     * @throws \Exception
     */
    private function sshExec(SSH2 &$ssh, string $cmd, OutputInterface $output) {
        $res = $ssh->exec($cmd);
        $exitStatus = $ssh->getExitStatus();
        if ($exitStatus !== 0) {
            $err = $ssh->getStdError();
            $output->writeln("<error>Error: $err</error>");
            $output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                ' Line: ' . __LINE__ . "\n $err</error>");
            throw new \Exception();
        }

        return $res;
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

        $verbose = false;
        if ($output->isVerbose()) {
            $verbose = true;
        }

        if (!isset($backup_name)) {
            $backup_name = $timestamp;
        }
        $output->writeln("<info>$this->start backup started</info>");
        // TODO: Change this to use db service and ssh
        if ($no_db_backup != TRUE) {
            $mkdir = ['mkdir', '-p', "$backup_path/$app"];
            $mysqldump = "mysqldump --port=$mysql_port -u $mysql_user " .
              "-p$mysql_password -h $mysql_host $mysql_db > /tmp/$backup_name.sql";
            $mkdirProcess = new Process($mkdir);
            $mkdirProcess->run();
            if ($mkdirProcess->isSuccessful()) {
                $ssh = new SSH2($server, $ssh_port, $timeout);
                $key = new RSA();
                $contents = file_get_contents($identityFile);
                $key->loadKey($contents);
                if (!$ssh->login($user, $key)) {
                    $err = $ssh->getErrors();
                    $output->writeln("<error>Error: could not ssh to $server.</error>");
                    $output->writeln("<error>SSH error: $err</error>");
                    throw new \Exception();
                }

                // Take the db dump on the remote server.
                $this->sshExec($ssh, $mysqldump, $output);
                $check = $this->sshExec($ssh, "ls -lh /tmp/$backup_name.sql", $output);
                $output->writeln("<comment>List backup: $check</comment>", OutputInterface::VERBOSITY_VERBOSE);
                if (strpos($check, "$backup_name") !== FALSE) {
                    $output->writeln("<comment>Backup was found on remote server.</comment>",
                        OutputInterface::VERBOSITY_VERBOSE);
                } else {
                    throw new \Exception("Could not find backup on remote server.");
                }

                // Tar the dump.
                $tarCmd = "tar -czvf /tmp/$backup_name.sql.tar /tmp/$backup_name.sql";
                $this->sshExec($ssh, $tarCmd, $output);

                // Rsync the database from the remote server.
                $sshCommand = "ssh -p $ssh_port";
                if (isset($sshCommand)) {
                    $sshCommand .= " -i $identityFile";
                }
                if ($verbose) {
                    $sshCommand .= ' -o LogLevel=VERBOSE';
                    $rsyncOptions = '-vPaL';
                }
                else {
                    $sshCommand .= ' -o LogLevel=ERROR';
                    $rsyncOptions = '-qPaL';
                }
                $rsyncCommand = "rsync $rsyncOptions -e \"$sshCommand\" $user@$server:/tmp/$backup_name.sql.tar $backup_path/$app/$backup_name.sql.tar";
                $rsyncProcess = Process::fromShellCommandline($rsyncCommand);
                $rsyncProcess->setTimeout($timeout);
                if ($output->isVerbose()) {
                    $output->writeln("<comment>$this->cat Executing: {$rsyncProcess->getCommandLine()}</comment>");
                }
                try {
                    $rsyncProcess->mustRun();
                    if ($verbose) {
                        $output->writeln("<comment>$this->cat Backed up database to $backup_path/$app/$backup_name.sql</comment>");
                        $out = $rsyncProcess->getOutput();
                        $output->writeln("<comment>$this->cat $out</comment>");
                    }
                } catch (ProcessFailedException $e) {
                    $out = $rsyncProcess->getErrorOutput();
                    $output->writeln("<error>$out</error>");
                    return $rsyncProcess->getExitCode();
                }
                // Remove backup from remote server. We have it locally now.
                $this->sshExec($ssh, "rm -f /tmp/$backup_name.*", $output);
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
                if ($verbose) {
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
                    if ($verbose) {
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

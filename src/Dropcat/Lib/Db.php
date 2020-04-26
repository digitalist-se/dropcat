<?php
namespace Dropcat\Lib;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use mysqli;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Db
 *
 * Handles common database tasks
 *
 * @package Dropcat\Lib
 */
class Db
{
    public $fs;
    public $mark;
    public $output;
    public $verbose;

    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
        $this->fs = new Filesystem();
        $this->output = new ConsoleOutput();
        $style = new Styles();
        $mark = $style->heavyCheckMark();
        $this->mark = $style->colorize('yellow', $mark);
    }

    public function createUser($conf)
    {
        $mysql_root_user = $conf['mysql-root-user'];
        $mysql_root_pass = $conf['mysql-root-pass'];
        $mysql_host = $conf['mysql-host'];
        $new_mysql_user = $conf['mysql-user'];
        $new_mysql_pass = $conf['mysql-password'];
        $timeout = $conf['timeout'];

        // Create db user.
        $cmd = "mysql -u $mysql_root_user -p$mysql_root_pass -h $mysql_host " .
          " -e \"CREATE USER '$new_mysql_user'@'%' IDENTIFIED BY '$new_mysql_pass'\";";
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout($timeout);
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($this->verbose == true) {
            echo $process->getOutput();
        }
        // Flush Privileges.
        $cmd = "mysqladmin -u$mysql_root_user -p$mysql_root_pass -h $mysql_host FLUSH-PRIVILEGES";
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout($timeout);
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($this->verbose == true) {
            echo $process->getOutput();
        }
        $this->output->writeln('<info>' . $this->mark . ' database user created</info>');
    }

    public function createDb($conf)
    {
        $mysql_host = $conf['mysql-host'];
        $mysql_user = $conf['mysql-user'];
        $mysql_password = $conf['mysql-password'];
        $mysql_db = $conf['mysql-db'];
        $mysql_port = $conf['mysql-port'];
        $timeout = $conf['timeout'];
        $mysql_root_user = $conf['mysql-root-user'];
        $mysql_root_pass = $conf['mysql-root-pass'];
        $db_dump_path = null;

        if (isset($conf['db-dump-path'])) {
            $db_dump_path = $conf['db-dump-path'];
        }

        try {
            $mysqli = new mysqli(
                "$mysql_host",
                "$mysql_user",
                "$mysql_password"
            );
        } catch (\Exception $e) {
            echo $e->getMessage(), PHP_EOL;
            exit(1);
        }

        // If db does not exist.
        if ($mysqli->select_db("$mysql_db") === false) {
            // Fix privileges for db user.
            $cmd = "mysql -u $mysql_root_user -p$mysql_root_pass -h $mysql_host " .
              "-e \"GRANT ALL PRIVILEGES ON * . * TO '$mysql_user'@'%' IDENTIFIED BY '$mysql_password'\";";
            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout($timeout);
            $process->mustRun();
            // Executes after the command finishes.
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            if ($this->verbose == true) {
                echo $process->getOutput();
            }

            $cmd = [
              'mysqladmin',
              '-u',
              "$mysql_user",
              "-p$mysql_password",
              '-h',
              "$mysql_host",
              '-P',
              "$mysql_port",
              'create',
              "$mysql_db",
            ];
            $process = new Process($cmd);
            $process->setTimeout($timeout);
            $process->mustRun();
            // Executes after the command finishes.
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            if ($this->verbose == true) {
                echo $process->getOutput();
            }

            $this->output->writeln('<info>' . $this->mark . ' database created</info>');
        } else {
            if (isset($db_dump_path)) {
                $cmd = "mysqldump -u $mysql_user -p$mysql_password -h $mysql_host -P $mysql_port $mysql_db > $db_dump_path";
                $process = Process::fromShellCommandline($cmd);
                $process->setTimeout($timeout);
                $process->mustRun();
                // Executes after the command finishes.
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
                if ($this->verbose == true) {
                    echo $process->getOutput();
                }
                $this->output->writeln("<info>$this->mark database backed up to $db_dump_path</info>");
            }

            $this->output->writeln('<info>' . $this->mark . ' database exists</info>');
        }
    }
    public function backup($conf, $path)
    {

        extract($conf);

        $cmd = "mysqldump -u $user -p$pass -h $host -P $port $name > $path";
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(999);
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($this->verbose == true) {
            echo $process->getOutput();
        }
        $this->output->writeln("<info>$this->mark database backed up to $path</info>");
    }

    public function backupOverSSH($conf) {
        extract($conf);

        $ssh = new SSH2($server, $port);
        $ssh->setTimeout(999);
        $auth = new RSA();
        if (isset($ssh_key_password)) {
            $auth->setPassword($ssh_key_password);
        }
        $auth->loadKey($identity_file_content);

        try {
            $login = $ssh->login($user, $auth);
            if (!$login) {
                throw new \Exception('Login Failed using ' . $identity_file . ' and user ' . $user . ' at ' . $server
                  . ' ' . $ssh->getLastError());
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        $ssh->exec("mysqldump -u $user -p$pass -h $host -P $port $name > $path");

        return $ssh->getExitStatus();
    }

    public function import($conf, $path)
    {

        extract($conf);

        $cmd = "mysql -u $user -p$pass -h $host -P $port $name < $path";
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(999);
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($this->verbose == true) {
            echo $process->getOutput();
        }
        $this->output->writeln("<info>$this->mark database imported from $path</info>");
    }

    public function dumpTableName($conf, $path, $verbose)
    {

        extract($conf);

        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS " .
          "WHERE COLUMN_NAME LIKE '$column' AND TABLE_SCHEMA = '$name'";

        $cmd = "mysql -u $user -p$pass -h $host -P $port -e \"$query\" > $path";
        $process = Process::fromShellCommandline($cmd);

        $process->setTimeout(999);
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($verbose == true) {
            echo $process->getOutput();
        }
        $this->output->writeln("<info>$this->mark database table names with column $name dumped to $path</info>");
    }

    public function updateTable($conf, $path, $verbose)
    {

        // Create variables from array.
        extract($conf);

        $tables = file($path);

        foreach ($tables as $change_table) {
            $change_table = trim(preg_replace('/\s+/', '', $change_table));
            if ($change_table != 'TABLE_NAME') {
                $query = "UPDATE $change_table SET $column = '$change'";
                $cmd = "mysql -u $user -p$pass -h $host -P $port $name -e \"$query\"";
                $process = Process::fromShellCommandline($cmd);

                $process->setTimeout(999);
                $process->run();
                // Executes after the command finishes.
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                if ($verbose == true) {
                    echo $process->getOutput();
                    $this->output->writeln("<info>mysql -u $user -p$pass -h $host -P $port $name -e $query</info>");
                    $this->output->writeln("<info>$this->mark $change_table to use $change</info>");
                }
            }
        }

        $this->output->writeln("<info>$this->mark database table names with" .
          " column $name language changed to $change</info>");
    }
}

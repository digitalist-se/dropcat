<?php


namespace Dropcat\Services;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseService
 *
 * Wrap database functions using drush for easy SSH connections.
 * @package Dropcat\Services
 */
class DatabaseService
{
    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param array $conf
     * @return int
     * @throws \Exception
     */
    public function createDb(array $conf) {
        $mysqlRootUser = $conf['mysql-root-user'];
        $mysqlRootPass = $conf['mysql-root-pass'];
        $mysqlHost = $conf['mysql-host'];
        $mysqlPort = $conf['mysql-port'];
        $mysqlUser = $conf['mysql-user'];
        $mysqlPass = $conf['mysql-password'];
        $mysqlDb = $conf['mysql-db'];
        $identityFile = $conf['identityFile'];
        $sshUser = $conf['user'];
        $sshServer = $conf['server'];
        $sshPort = $conf['ssh-port'];
        $timeout = $conf['timeout'];

        $this->output->writeln("<comment>MYSQL Host: $mysqlHost, Port: $mysqlPort, Drupal User: $mysqlUser,
            Drupal DB: $mysqlDb, SSH User: $sshUser, Identity File: $identityFile</comment>",
            OutputInterface::VERBOSITY_VERBOSE);

        try {
            $ssh = new SSH2($sshServer, $sshPort, $timeout);
            $key = new RSA();
            $contents = file_get_contents($identityFile);
            $key->loadKey($contents);
            if (!$ssh->login($sshUser, $key)) {
                $err = $ssh->getErrors();
                $this->output->writeln("<error>Error: could not ssh to $sshServer.</error>");
                $this->output->writeln("<error>SSH error: $err</error>");
                throw new \Exception();
            }
            $create = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                " -e \"CREATE DATABASE $mysqlDb\";";
            $getDB = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                " -e \"SHOW DATABASES LIKE '$mysqlDb'\";";
            $grant = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                "-e \"GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE, LOCK TABLES ON $mysqlDb.* TO 
                '$mysqlUser'@'%' IDENTIFIED BY '$mysqlPass'\";";
            $flush = "mysqladmin -u$mysqlRootUser -p$mysqlRootPass -h $mysqlHost FLUSH-PRIVILEGES";

            // Create the db.
            $ssh->exec($create);
            $createExitStatus = $ssh->getExitStatus();
            if ($createExitStatus !== 0) {
                $err = $ssh->getStdError();
                if (strpos($err, 'database exists') !== FALSE) {
                    $this->output->writeln("<comment>Database $mysqlDb exists already.</comment>", OutputInterface::VERBOSITY_VERBOSE);
                } else {
                    $this->output->writeln("<error>Error: Could not create the database $mysqlDb.</error>");
                    $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                        ' Line: ' . __LINE__ . "\n $err</error>");
                    throw new \Exception();
                }
            }
            // Check that the db was created.
            $getDBOut = $ssh->exec($getDB);
            $dbString = strpos($getDBOut, $mysqlDb);
            if ($dbString !== FALSE) {
                $this->output->writeln("<info>Database created: $mysqlDb</info>");
            } else {
                $err = $ssh->getStdError();
                $this->output->writeln("<error>Error: Could not create the database $mysqlDb.</error>");
                $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                    ' Line: ' . __LINE__ . "\n $err</error>");
                throw new \Exception();
            }

            // Grant privileges.
            $ssh->exec($grant);
            $grantExitStatus = $ssh->getExitStatus();
            if ($grantExitStatus !== 0) {
                $err = $ssh->getStdError();
                $this->output->writeln("<error>Error: Could not grant the user $mysqlUser access to $mysqlDb.</error>");
                $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                    ' Line: ' . __LINE__ . "\n $err</error>");
                throw new \Exception();
            }
            // Flush privileges.
            $ssh->exec($flush);
            $flushExitStatus = $ssh->getExitStatus();
            if ($flushExitStatus !== 0) {
                $err = $ssh->getStdError();
                $this->output->writeln("<error>Error: Could not flush privileges for $mysqlUser.</error>");
                $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                    ' Line: ' . __LINE__ . "\n $err</error>");
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->output->writeln("<error>Error: Could not create the database.</error>");
            $this->output->writeln("<error>$msg</error>");
            throw new \Exception();
        }

        return 0;
    }

    /**
     * @param array $conf
     * @param bool $verbose
     * @return int
     * @throws \Exception
     */
    public function createDatabaseUser(array $conf, bool $verbose = FALSE) {
        $mysqlRootUser = $conf['mysql-root-user'];
        $mysqlRootPass = $conf['mysql-root-pass'];
        $mysqlHost = $conf['mysql-host'];
        $mysqlPort = $conf['mysql-port'];
        $mysqlUser = $conf['mysql-user'];
        $mysqlPass = $conf['mysql-password'];
        $mysqlDb = $conf['mysql-db'];
        $identityFile = $conf['identityFile'];
        $sshUser = $conf['user'];
        $sshServer = $conf['server'];
        $sshPort = $conf['ssh-port'];
        $timeout = $conf['timeout'];

        try {
            $ssh = new SSH2($sshServer, $sshPort, $timeout);
            if ($verbose) {
                $ssh->disableQuietMode();
            }
            $key = new RSA();
            $contents = file_get_contents($identityFile);
            $key->loadKey($contents);
            if (!$ssh->login($sshUser, $key)) {
                $err = $ssh->getErrors();
                $this->output->writeln("<error>Error: could not ssh to $sshServer on port $sshPort as $sshUser with key $identityFile.</error>");
                if (is_array($err)) {
                    foreach ($err as $error) {
                        $this->output->writeln("<error>SSH error: $error</error>");
                    }
                }
                throw new \Exception();
            }
            // Create db user.
            $getUser = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                " -e \"SELECT user,host FROM mysql.user WHERE user LIKE '$mysqlUser'\";";
            $create = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                " -e \"CREATE USER '$mysqlUser'@'%' IDENTIFIED BY '$mysqlPass'\";";
            // Flush Privileges.
            $flush = "mysqladmin -u$mysqlRootUser -p$mysqlRootPass -h $mysqlHost FLUSH-PRIVILEGES";
            $exec = $ssh->exec($getUser);
            $userString = strpos($exec, $mysqlUser);
            if ($userString !== FALSE) {
                $this->output->writeln("<comment>Mysql user exists already: $mysqlUser</comment>", OutputInterface::VERBOSITY_NORMAL);
                return 0;
            }

            // Create db user.
            $ssh->exec($create);
            $createExitStatus = $ssh->getExitStatus();
            if ($createExitStatus !== 0) {
                $err = $ssh->getStdError();
                $this->output->writeln("<error>Error: Could not create the user $mysqlUser.</error>");
                $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                    ' Line: ' . __LINE__ . "\n $err</error>");
                throw new \Exception();
            }
            // We don't get sensible output from the create user command, let's check again if it exists.
            $exec = $ssh->exec($getUser);
            $userString = strpos($exec, $mysqlUser);
            if ($userString !== FALSE) {
                $this->output->writeln("<comment>Mysql user created: $mysqlUser</comment>", OutputInterface::VERBOSITY_NORMAL);
                return 0;
            }

            // Flush privileges.
            $ssh->exec($flush);
            $flushExitStatus = $ssh->getExitStatus();
            if ($flushExitStatus !== 0) {
                $err = $ssh->getStdError();
                $this->output->writeln("<error>Error: Could not flush privileges for user $mysqlUser.</error>");
                $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                    ' Line: ' . __LINE__ . "\n $err</error>");
                throw new \Exception();
            }
            $ssh->reset();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->output->writeln("<error>Error: Exception when trying to create the user $mysqlUser.</error>");
            $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                ' Line: ' . __LINE__ . "\n $msg</error>");
            throw new \Exception();
        }

        return 0;
    }
}
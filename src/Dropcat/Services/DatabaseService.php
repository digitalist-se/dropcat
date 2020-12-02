<?php


namespace Dropcat\Services;

use mysqli;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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

    const SQL_CONNECT = 'sql-connect';
    const SQL_QUERY = 'sql:query';
    const SQL_CREATE = 'sql:create';

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Try connecting to database defined in the drush alias.
     * @param string $drushAlias
     * @return bool
     * @throws \Exception
     */
    public function dbExists(string $drushAlias) : bool
    {
        $this->output->writeln("<comment>Using drush alias: $drushAlias</comment>", OutputInterface::VERBOSITY_VERBOSE);
        $p = new Process(['drush', "@$drushAlias", self::SQL_QUERY , 'SELECT * FROM users LIMIT 1', '-vvv']);
        $p->setTimeout(60);
        try {
            $p->mustRun();
            $out = $p->getOutput();
            $errOut = $p->getErrorOutput();
            // Drush does not return error code, but writes to stderr in case of failure :facepalm:
            if (!empty($errOut)) {
                $this->output->writeln("<comment>Error: $errOut</comment>", OutputInterface::VERBOSITY_NORMAL);
                $this->output->writeln("<error>Error: Could not connect to the database.</error>");
                throw new \Exception("Database connection failed.");
            }
            $this->output->writeln("<info>Successfully connected to the database.</info>");
            $this->output->writeln("<comment>Output: $out</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } catch (ProcessFailedException $e) {
            $err = $p->getErrorOutput();
            $this->output->writeln("<error>Error: Could not connect to the database.</error>");
            $this->output->writeln("<error>Error: $err </error>");
            throw new \Exception("Database connection failed.");
        }

        return true;
    }

    public function createDb(string $drushAlias, array $conf) : bool
    {
        $mysqlRootUser = $conf['mysql-root-user'];
        $mysqlRootPass = $conf['mysql-root-pass'];
        $mysqlHost = $conf['mysql-host'];
        $mysqlPort = $conf['mysql-port'];
        $mysqlUser = $conf['mysql-user'];
        $mysqlPass = $conf['mysql-password'];
        $mysqlDb = $conf['mysql-db'];


        $this->output->writeln("<comment>MYSQL Host: $mysqlHost, Port: $mysqlPort, User: $mysqlUser, DB: $mysqlDb</comment>",
            OutputInterface::VERBOSITY_VERBOSE);

        try  {
            $this->dbExists($drushAlias);
            $this->output->writeln("<info>Database exists already.</info>");
            return true;
        } catch (\Exception $e) {
            $this->output->writeln("<info>Database does not exist. Creating...</info>");
        }

        # "mysql://drupal_db_user:drupal_db_password@127.0.0.1/drupal_db"
        $dbURL = "mysql://$mysqlUser:$mysqlPass@$mysqlHost:$mysqlPort/$mysqlDb";
        $this->output->writeln("<comment>Using drush alias: $drushAlias</comment>", OutputInterface::VERBOSITY_VERBOSE);
        $p = new Process(['drush', "@$drushAlias", self::SQL_CREATE , "--db-su=$mysqlRootUser --db-su-pw=$mysqlRootPass --db-url=$dbURL"]);
        $p->setTimeout(60);
        try {
            $p->mustRun();
            $out = $p->getOutput();
            $this->output->writeln("<info>Successfully created the database.</info>");
            $this->output->writeln("<comment>Output: $out</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } catch (ProcessFailedException $e) {
            $err = $p->getErrorOutput();
            $this->output->writeln("<error>Error: Could not create the database.</error>");
            $this->output->writeln("<error>Error: $err </error>");
            return false;
        }

        return true;
    }

    public function createDbOverSsh(array $conf) {
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

        $this->output->writeln("<comment>MYSQL Host: $mysqlHost, Port: $mysqlPort, User: $mysqlUser,
            DB: $mysqlDb, SSH User: $sshUser, Identity File: $identityFile</comment>",
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
                return false;
            }
            $create = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                " -e \"CREATE DATABASE $mysqlDb\";";
            $grant = "mysql -u $mysqlRootUser -p$mysqlRootPass -h $mysqlHost " .
                "-e \"GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE, LOCK TABLES ON '$mysqlDb'.* TO 
                '$mysqlUser'@'%' IDENTIFIED BY '$mysqlPass'\";";
            $flush = "mysqladmin -u$mysqlRootUser -p$mysqlRootPass -h $mysqlHost FLUSH-PRIVILEGES";
            $ssh->exec($create);
            $ssh->exec($grant);
            $ssh->exec($flush);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->output->writeln("<error>Error: Could not create the database.</error>");
            $this->output->writeln("<error>$msg</error>");
            exit(1);
        }
    }

    /**
     * @param array $conf
     * @param bool $verbose
     * @return int
     */
    public function createUserOverSsh(array $conf, bool $verbose = FALSE) {
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
                return 1;
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
                $this->output->writeln("<comment>Mysql user already exists: $mysqlUser</comment>", OutputInterface::VERBOSITY_NORMAL);
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
                return 1;
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
                return 1;
            }
            $ssh->reset();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->output->writeln("<error>Error: Exception when trying to create the user $mysqlUser.</error>");
            $this->output->writeln('<error>In: File: ' . __FILE__ . ' Function: '.__FUNCTION__ .
                ' Line: ' . __LINE__ . "\n $msg</error>");
            exit(1);
        }

        return 0;
    }
}
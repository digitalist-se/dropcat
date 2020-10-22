<?php


namespace Dropcat\Services;

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
     */
    public function dbExists(string $drushAlias) : bool
    {
        $this->output->writeln("<comment>Using drush alias: $drushAlias</comment>", OutputInterface::VERBOSITY_VERBOSE);
        //$p = new Process(['drush', "@$drushAlias", self::SQL_CONNECT]);
        $p = new Process(['drush', "@$drushAlias", self::SQL_QUERY , 'SELECT * FROM users LIMIT 1']);
        $p->setTimeout(60);
        try {
            $p->mustRun();
            $out = $p->getOutput();
            $this->output->writeln("<info>Successfully connected to the database.</info>");
            $this->output->writeln("<comment>Output: $out</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } catch (ProcessFailedException $e) {
            $err = $p->getErrorOutput();
            $this->output->writeln("<error>Error: Could not connect to the database.</error>");
            $this->output->writeln("<error>Error: $err </error>");
            return false;
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

        if ($this->dbExists($drushAlias)) {
            $this->output->writeln("<info>Database exists already.</info>");
            return true;
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
}
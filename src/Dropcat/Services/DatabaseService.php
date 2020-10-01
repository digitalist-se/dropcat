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
        $p = new Process(['drush', "@$drushAlias", 'sql:query' , 'SELECT * FROM users LIMIT 1']);
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
}
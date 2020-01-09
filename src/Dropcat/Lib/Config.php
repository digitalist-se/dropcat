<?php
namespace Dropcat\Lib;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Config
 *
 * Checking if it is Drupal, and which version.
 *
 * @package Dropcat\Lib
 */
class Config
{
    public $mark;
    public $output;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->output = new ConsoleOutput();
        $style = new Styles();
        $mark = $style->heavyCheckMark();
        $this->mark = $style->colorize('yellow', $mark);
    }

    /**
     * @param $config
     * @param $verbose
     */
    public function import($config, $verbose)
    {
        $alias = $config['drush-alias'];
        $v = ' -q';
        if ($verbose == true) {
            $v = ' -v';
        }
        $import= new Process(
            ['drush', "@$alias", "cim", "--yes", "$v"]
        );
        $import->setTimeout(999);
        $import->run();
        // executes after the command finishes
        if (!$import->isSuccessful()) {
            throw new ProcessFailedException($import);
        }
        echo $import->getOutput();
        $this->output->writeln("<info>$this->mark config imported for $alias</info>");
    }

    /**
     * @param $config
     * @param $verbose
     */
    public function silentImport($config, $verbose)
    {
        $alias = $config['drush-alias'];
        $v = ' -q';
        if ($verbose == true) {
            $v = ' -v';
        }
        $import= new Process(
            ['drush', "@$alias", 'cim', '--yes', '--quiet']
        );
        $import->disableOutput();
        $import->setTimeout(999);
        $import->run();

        $this->output->writeln("<info>$this->mark config imported for $alias</info>");
    }


    /**
     * @param $config
     * @param $verbose
     */
    public function importPartial($config, $verbose)
    {
        $alias = $config['drush-alias'];
        $v = ' -q';
        if ($verbose == true) {
            $v = ' -v';
        }
        $import= new Process(
            ['drush', "@$alias", "cim", "--partial", "--yes", "$v"]
        );
        $import->setTimeout(999);
        $import->run();
        if (!$import->isSuccessful()) {
            throw new ProcessFailedException($import);
        }
        echo $import->getOutput();
        $this->output->writeln("<info>$this->mark config imported for $alias</info>");
    }

    /**
     * @param $config
     * @param $split
     * @param $verbose
     */
    public function configSplitExport($config, $split, $verbose)
    {
        $alias = $config['drush-alias'];
        $v = ' -q';
        if ($verbose == true) {
            $v = ' -v';
        }
        $enable = new Process(
            ['drush', "@$alias", 'en', 'config_split', '--yes', '$v', '&&', 'drush', '@$alias', 'cc', 'drush', '--yes', "$v"]
        );
        $enable->setTimeout(999);
        $enable->run();
        // executes after the command finishes
        if (!$enable->isSuccessful()) {
            throw new ProcessFailedException($enable);
        }
        echo $enable->getOutput();

        $import= new Process(
            ['drush', "@$alias", 'csex', "$split", '--yes', '$v']
        );
        $import->setTimeout(999);
        $import->run();
        if (!$import->isSuccessful()) {
            throw new ProcessFailedException($import);
        }
        echo $import->getOutput();
        $this->output->writeln("<info>$this->mark config imported for $alias</info>");
    }
}

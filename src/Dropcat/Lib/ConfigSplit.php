<?php
namespace Dropcat\Lib;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class ConfigSplit
 *
 * Functions for handling drupal config splt.
 *
 * @package Dropcat\Lib
 */
class ConfigSplit
{

    public $verbose;

    /**
     * ConfigSplit constructor.
     * @param bool $verbose
     */
    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
    }

    /**
     * @param $config
     */
    public function export($config)
    {
        $alias = $config['drush-alias'];
        $v = '';
        if ($this->verbose == true) {
            $v = ' -v';
        }
        $task= new Process(
            ['drush', "@$alias", 'csex', '--yes', "$v"]
        );
        $task->setTimeout(999);
        $task->run();
        // executes after the command finishes
        if (!$task->isSuccessful()) {
            throw new ProcessFailedException($task);
        }
        if ($this->verbose == true) {
            echo $task->getOutput();
        }
    }
}

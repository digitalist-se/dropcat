<?php
namespace Dropcat\Lib;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class UpdateDb
 *
 * Checking if it is Drupal, and which version.
 *
 * @package Dropcat\Lib
 */
class UpdateDb
{

    public function run($config, $verbose)
    {
        $alias = $config['drush-alias'];
        $v = '';
        if ($verbose == true) {
            $v = ' -v';
        }
        $install= new Process(
            "drush @$alias updb --yes $v"
        );
        $install->setTimeout(999);
        $install->run();
        // executes after the command finishes
        if (!$install->isSuccessful()) {
            throw new ProcessFailedException($install);
        }
        echo $install->getOutput();
    }
}

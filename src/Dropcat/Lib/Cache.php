<?php
namespace Dropcat\Lib;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Cache
 *
 * Rebuild cache
 *
 * @package Dropcat\Lib
 */
class Cache
{

    public $mark;
    public $output;

    public function __construct()
    {
        $style = new Styles();
        $mark = $style->heavyCheckMark();
        $this->mark = $style->colorize('yellow', $mark);
        $this->output = new ConsoleOutput();
    }

    /**
     * @param $config
     * @param $verbose
     * @return int
     */
    final function rebuild($config, $verbose)
    {
        // create strings from array.
        $alias = $config['drush-alias'];

        $this->output->writeln("<info>$this->mark starting rebuilding cache</info>");
        $rebuild = new Process(
            ['drush' ,"@$alias cr"]
        );
        $rebuild->setTimeout(999);
        $rebuild->run();

        // executes after the command finishes
        if (!$rebuild->isSuccessful()) {
            throw new ProcessFailedException($rebuild);
        }
        echo $rebuild->getOutput();
        $this->output->writeln("<info>$this->mark finnished rebuilding cache</info>");
        return 0;
    }
}

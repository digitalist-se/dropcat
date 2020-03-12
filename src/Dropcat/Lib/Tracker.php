<?php
namespace Dropcat\Lib;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class CheckDrupal
 *
 * Checking if it is Drupal, and which version.
 *
 * @package Dropcat\Lib
 */
class Tracker
{
    public $fs;
    public $mark;
    public $verbose;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    private $output;

    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
        $this->fs = new Filesystem();
        $this->output = new ConsoleOutput();
        $style = new Styles();
        $mark = $style->heavyCheckMark();
        $this->mark = $style->colorize('yellow', $mark);
    }

    public function addDefault($conf, $app_name, $dir, $multi, $env)
    {

        $file = new Filesystem();
        $yaml = Yaml::dump($conf, 4, 2);
        $recreate_tracker = false;

        $default_tracker = "$dir/default/$app_name-$env.yml";

        // Check if the tracker need to be recreated.
        if (file_exists($default_tracker)) {
            $existing_conf = Yaml::parse(file_get_contents($default_tracker));
            if ($existing_conf !== $conf && $multi == false) {
                $recreate_tracker = true;
            }
        }

        if (!file_exists($default_tracker) || $recreate_tracker == true) {
            try {
                $file->dumpFile($default_tracker, $yaml);
            } catch (IOExceptionInterface $e) {
                echo "An error occurred while creating your file at " . $e->getPath();
                exit;
            }
            $this->output->writeln('<info>' . $this->mark . ' default tracker created</info>');
        } else {
            $this->output->writeln('<info>' . $this->mark . ' default tracker exists</info>');
        }
    }

    public function addMulti($tracker)
    {
        // read tracker file
        $tracker_file = $tracker['tracker-file'];
        $new_site = $tracker['new-site'];
        $dir = $tracker['tracker-dir'];
        $id = $tracker['app-name'];

        try {
            $conf = Yaml::parse(file_get_contents($tracker_file));
        } catch (ParseException $e) {
            echo $e->getMessage() . "\n";
        }
        foreach ($new_site as $key => $value) {
            $conf['sites'][$key] = $value;
        }

        $file = new Filesystem();
        $yaml = Yaml::dump($conf, 4, 2);
        try {
            $file->dumpFile($tracker_file, $yaml);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your file at " . $e->getPath();
        }
    }

    public function rollback($conf, $name)
    {
        $file = new Filesystem();
        $yaml = Yaml::dump($conf, 4, 2);

        try {
            $file->dumpFile($name, $yaml);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your file at " . $e->getPath();
        }
    }

    public function read($tracker_file)
    {
        $conf = [];
        try {
            $conf = Yaml::parse(file_get_contents($tracker_file));
        } catch (ParseException $e) {
            echo $e->getMessage() . "\n";
        }
        $sites = $conf['sites'];
        return $sites;
    }
}

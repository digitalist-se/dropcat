<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Dropcat\Lib\Tracker;
use Symfony\Component\Finder\Finder;
use Exception;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


use Dropcat\Lib\Mail;

class SitesBackupCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = '<info>Display info about installed drupal multi-sites</info>';

        $this->setName("sites:backup")
          ->setDescription("Uses the tracker dir for making backups of sites.")
          ->setHelp($HelpText)
          ->setDefinition(
              [
                new InputOption(
                    'config',
                    'c',
                    InputOption::VALUE_OPTIONAL,
                    'Config file',
                    null
                ),
                new InputOption(
                    'tracker-dir',
                    't',
                    InputOption::VALUE_OPTIONAL,
                    'tracker directory',
                    null
                ),
              ]
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config_file = $input->getOption('config');
        $tracker_file_directory = $input->getOption('tracker-dir');

        if (isset($config_file)) {
            try {
                $conf = Yaml::parse(file_get_contents($config_file));
                if (!$conf) {
                    throw new Exception('config file does not exist');
                }
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
                exit(1);
            }
        }

        // set tracker dir to what is in config, if it does exist as an option.
        if (!isset($tracker_file_directory) && (isset($conf['tracker-dir']))) {
            $tracker_file_directory = $conf['tracker-dir'];
        }

        try {
            if (!isset($tracker_file_directory)) {
                throw new Exception('tracker directory is not set');
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        try {
            $check_backup_dir = $this->checkBackupDir($conf['backup-dir']);
            if (!$check_backup_dir) {
                throw new Exception('backup dir does not exist, or is not writable');
            }
        } catch (Exception $e) {
        }

        $this->applicationsBackup($tracker_file_directory, $conf);

        return 0;
    }

    public function applicationsBackup($tracker_file_directory, $conf)
    {
        $finder = new Finder();
        $finder->files()->in($tracker_file_directory);

        foreach ($finder as $file) {
            $tracker_file = $file->getRealPath();
            $ext = $file->getExtension();
            // tracker files has a yml extension
            if ($ext == 'yml') {
                $tracker = new Tracker();
                $sites = $tracker->read($tracker_file);
                $get_file_info = pathinfo($tracker_file);
                $application = $get_file_info['filename'];
                $backup_dir = $conf['backup-dir'];
                $full_backup_path = "$backup_dir/$application";
                try {
                    $create = $this->createAppBackupDir($full_backup_path);
                    if (!$create) {
                        throw new Exception('application backup directories could not be created');
                    }
                } catch (Exception $e) {
                    echo $e->getMessage() . "\n";
                    exit(1);
                }
                foreach ($sites as $site) {
                    $db_user = $site['db']['user'];
                    $db_pass = $site['db']['pass'];
                    $db_host = $site['db']['host'];
                    $db_name = $site['db']['name'];
                    $this->makeDbBackup($db_user, $db_pass, $db_host, $db_name, $full_backup_path, $application, $conf);
                }
            }
        }
    }

    public function makeDbBackup($db_user, $db_pass, $db_host, $db_name, $full_backup_path, $application, $conf)
    {
        $process = Process::fromShellCommandline(
            "mysqldump -u$db_user  -p$db_pass -h $db_host $db_name > $full_backup_path/$db_name.sql"
        );
        $process->setTimeout(9999);
        $process->run();
        // Executes after the command finishes.
        if (!$process->isSuccessful()) {
            $body = "Could not backup $db_name from $application\nTracker file name is $application.yml";
            $subject = "[dropcat] Error: in $application";
            $this->sendMail($conf, $subject, $body);
            // we do not throw exception, because we want to process the next backup.
        }
        if ($process->isSuccessful()) {
            $gzip = new Process(['gzip', '$full_backup_path/$db_name.sql', '-f']);
            $gzip->setTimeout(9999);
            $gzip->run();
            // Executes after the command finishes.
            if (!$gzip->isSuccessful()) {
                $body = "Could not gzip $db_name from $application\nTracker file name is $application.yml";
                $subject = "[dropcat] Error: gzip error in $application";
                $this->sendMail($conf, $subject, $body);
                // we do not throw exception, because we want to process the next backup.
            }
        }
    }

    public function createAppBackupDir($full_backup_path)
    {

        if (!is_dir($full_backup_path)) {
            $process = new Process(
                ['mkdir', '-p', "$full_backup_path"]
            );
            $process->mustRun();
            return true;
        } else {
            return true;
        }
    }

    public function checkBackupDir($dir)
    {
        if (is_dir($dir)) {
            if (is_writable($dir)) {
                return true;
            }
        } else {
            return false;
        }
        return false;
    }

    public function sendMail($conf, $subject, $body)
    {
        $mail = new Mail();
        $mail->send($conf, $subject, $body);
    }
}

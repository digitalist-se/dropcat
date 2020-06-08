<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Dropcat\Lib\CheckDrupal;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use ComposerLockParser\ComposerInfo;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;

class SecurityDrupalCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = '<info>check drupal release security</info>';

        $this->setName("security:drupal")
          ->setDescription("check drupal security")
          ->setDefinition(
              array(
              new InputOption(
                  'lock-file',
                  'l',
                  InputOption::VALUE_OPTIONAL,
                  'Lock flle path',
                  './composer.lock'
              ),
              new InputOption(
                  'api',
                  'a',
                  InputOption::VALUE_OPTIONAL,
                  'API base url',
                  'https://drupal-versions.dglive.net'
              ),
              new InputOption(
                  'voldemort',
                  'm',
                  InputOption::VALUE_NONE,
                  'be evil, require latest'
              ),
              new InputOption(
                  'manual',
                  null,
                  InputOption::VALUE_OPTIONAL,
                  'Manual check version, do not use lock file',
                  null
              ),
              )
          )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock_file = $input->getOption('lock-file');
        $api = $input->getOption('api');
        $be_evil = $input->getOption('voldemort') ? true : false;
        $version = $input->getOption('manual');

        $check_drupal = new CheckDrupal();
        $version_drupal = $check_drupal->version();

        if (!isset($version)) {
            if ($version_drupal == '8') {
                $read_lock = new ComposerInfo($lock_file);
                $parse = $read_lock->getPackages();

                foreach ($parse as $package) {
                    $name[] = $package->getName();
                    $version[] = $package->getVersion();
                }

                $key = array_search('drupal/core', $name);
                if (!isset($key)) {
                    $key = array_search('drupal/drupal', $name);
                }
                if ($key === false) {
                    echo "drupal not found";
                    exit();
                }

                if (isset($key) && isset($version)) {
                    $existing = $version["$key"];
                    $this->getVersion($existing, $api, $be_evil, $output);
                }
            }
            if ($version_drupal == '7') {
                // @todo: we need to scan core files if they are commited and
                // not using a make file
                $find_d7_command = 'find . -maxdepth 1 -type f \( -iname \*.make -o -iname \*.make \)| xargs grep "\[\drupal]\[version"';
                $run_process = Process::fromShellCommandline($find_d7_command);
                $run_process->run();
                $get_output = $run_process->enableOutput();
                $line = $get_output->getOutput();
                $version = substr($line, -5);
                $version = trim($version);
                $this->getVersion($version, $api, $be_evil, $output);
            }
        } else {
            $this->getVersion($version, $api, $be_evil, $output);
        }

        return 0;
    }

    public function getVersion($version, $api, $be_evil, $output)
    {

        try {
            $client = new Client(['base_uri' => "$api"]);

            $res = $client->request(
                'GET',
                "/api/v1/version/$version",
                ['allow_redirects' => true]
            );

            if ($res->hasHeader('400')) {
                throw new Exception('drupal version not found');
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        $decoded = json_decode($res->getBody(), true);
        $status = $decoded[0]['status'];

        $ok = [
          'latest',
        ];
        if ($be_evil === false) {
            $ok = [
              'latest',
              'deprecated',
            ];
        }

        if (in_array($status, $ok)) {
            if ($status === 'deprecated') {
                $output->writeln("<info>$this->mark drupal version $version is deprecated</info>");
                $output->writeln("<error>THIS VERSION ($version) SHOULD NOT BE DEPLOYED TO PRODUCTION!</error>");
            } else {
                $output->writeln("<info>$this->heart drupal version $version is ok</info>");
            }
        } else {
            if ($status == 'deprecated') {
                $output->writeln("<info>$this->error drupal version $version is deprecated</info>");
                $output->writeln("<error>ERROR!!!</error>");
                exit(1);
            } else {
                $output->writeln("<info>$this->error drupal version $version is not secure</info>");
                $output->writeln("<error>ERROR!!! CAN NOT BE DEPLOYED TO PRODUCTION!</error>");
                exit(1);
            }
        }
    }
}

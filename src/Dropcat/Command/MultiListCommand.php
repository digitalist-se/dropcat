<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Dropcat\Lib\Tracker;

class MultiListCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = '<info>Display info about installed drupal multi-sites</info>';

        $this->setName("multi:list")
          ->setDescription("list drupal multi sites")
          ->setHelp($HelpText)
          ->setDefinition(
              [
                new InputOption(
                    'tracker-file',
                    't',
                    InputOption::VALUE_REQUIRED,
                    'tracker file',
                    null
                ),
                new InputOption(
                    'format',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'The output format (json or txt)',
                    'txt'
                ),
                new InputOption(
                    'info',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'What to display (defaults to site)',
                    'site'
                ),
                new InputOption(
                    'seperator',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'How to seperate output, only valid for txt format',
                    'newline'
                ),
              ]
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tracker_file = $input->getOption('tracker-file');
        $format = $input->getOption('format');
        $info = $input->getOption('info');
        $seperator = $input->getOption('seperator');

        $tracker = new Tracker();
        $sites = $tracker->read($tracker_file);

        $conf = Yaml::parse(file_get_contents($tracker_file));

        $print[] = '';

        if ($info == 'site') {
            foreach ($sites as $site => $siteProperty) {
                if ($site !== 'default') {
                    $print[] = $site;
                }
            }
        }
        if ($info == 'site-domain') {
            foreach ($conf['sites'] as $site) {
                if (isset($site['web']['site-domain'])) {
                    $print[] = $site['web']['site-domain'];
                }
            }
        }
        if ($info == 'drush-alias') {
            foreach ($conf['sites'] as $site) {
                if (isset($site['drush']['alias'])) {
                    $print[] = $site['drush']['alias'];
                }
            }
        }
          $print = array_filter($print);
        if ($format == 'json') {
            $out = json_encode($print);
        } else {
            if ($seperator == 'space') {
                $out = implode(" ", $print);
            }
            if ($seperator == 'comma') {
                $out = implode(",", $print);
            }
            if ($seperator == 'newline') {
                $out = implode("\n", $print);
            }
        }
            $output->writeln("$out");

        return 0;
    }
}

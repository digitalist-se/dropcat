<?php

namespace Dropcat\Command;

use Dropcat\Lib\Config;
use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Dropcat\Lib\Tracker;
use Symfony\Component\Console\Input\ArrayInput;
use Dropcat\Lib\Db;
use Dropcat\Lib\UUID;
use Dropcat\Lib\Name;
use Dropcat\Lib\Language;
use Dropcat\Lib\Rsync;
use Dropcat\Lib\Cache;
use Dropcat\Lib\ConfigSplit;

class MultiCloneCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = '<info>Clone a drupal site</info>';

        $this->setName("multi:clone")
          ->setDescription("clone a drupal site")
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
                    'site',
                    's',
                    InputOption::VALUE_REQUIRED,
                    'site',
                    null
                ),
                new InputOption(
                    'new-site',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'new site',
                    null
                ),
                new InputOption(
                    'profile',
                    'p',
                    InputOption::VALUE_REQUIRED,
                    'profile',
                    null
                ),
                new InputOption(
                    'language',
                    'l',
                    InputOption::VALUE_REQUIRED,
                    'language',
                    null
                ),
                new InputOption(
                    'config-split-settings',
                    'c',
                    InputOption::VALUE_REQUIRED,
                    'config split settings',
                    null
                ),
                new InputOption(
                    'server-alias',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Server alias',
                    null
                ),
              ]
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tracker_file = $input->getOption('tracker-file');
        $site = $input->getOption('site');
        $new_site = $input->getOption('new-site');
        $profile = $input->getOption('profile');
        $language = $input->getOption('language');
        $config_split_settings = $input->getOption('config-split-settings');
        $server_alias = $input->getOption('server-alias');

        $verbose = false;

        if ($output->isVerbose()) {
            $verbose = true;
        }

        $tracker = new Tracker();
        $sites = $tracker->read($tracker_file);

        if (isset($sites["$site"]) && is_array($sites["$site"])) {
            $to_clone = $sites["$site"];
        } else {
            $output->writeln("not a valid site to clone");
            exit;
        }
        // run prepare to create a site
        $command = $this->getApplication()->find('prepare');
        $arguments = [
          'command' => 'prepare',
          '--create-site' => $new_site,
          '--config-split-folder' => "sites/$new_site/sync",
          '--profile' => $profile,
          '--lang' => $language,
          '--config-split-settings' => $config_split_settings,
          '--no-email' => true,
        ];
        if (isset($server_alias)) {
            $arguments['--server-alias'] = $server_alias;
        }


        $prepareInput = new ArrayInput($arguments);
        $returnCode = $command->run($prepareInput, $output);

        $db = $to_clone['db'];

        $conf = [
            'name' => $db['name'],
            'user' => $db['user'],
            'pass' => $db['pass'],
            'host' => $db['host'],
            'port' => '3306',
        ];

        $random_name = UUID::v4();
        $path = "/tmp/$random_name.sql";

        $db_backup = new Db();
        $db_backup->backup($conf, $path);

        $clean_site_name = Name::site($new_site);
        $tracker = new Tracker();
        $sites = $tracker->read($tracker_file);

        $new_site_db = $sites["$clean_site_name"]['db'];

        $conf = [
          'name' => $new_site_db['name'],
          'user' => $new_site_db['user'],
          'pass' => $new_site_db['pass'],
          'host' => $new_site_db['host'],
          'port' => '3306',
        ];

        $db_import = new Db();
        $db_import->import($conf, $path);

        $drush_alias = $sites["$clean_site_name"]['drush']['alias'];

        // set language.
        $set_language = new Language();
        $set_language->setLang($language, $drush_alias, $verbose);

        $config = [
          'drush-alias' => $drush_alias,
        ];


        $config_export = new ConfigSplit();
        $config_export->export($config);

        // do a silent config import - it could soft fail because of language.
        // so we do not want a error in the console.
        // this is a hack....
        $silent_import = new Config();
        $silent_import->silentImport($config, $verbose);

        // set language again to override the config import if needed.
        $set_language->setLang($language, $drush_alias, $verbose);

        $config_export = new ConfigSplit();
        $config_export->export($config);

        // normal config import, this should be nothing normally, but just in case
        $silent_import = new Config();
        $silent_import->import($config, $verbose);

        $random_name = UUID::v4();
        $path = "/tmp/$random_name.sql";

        $conf['column'] = 'langcode';

        // Dump table names with column langcode.
        $dumpLang = new Db();
        $dumpLang->dumpTableName($conf, $path, $verbose);

        // set what to change to.
        $conf['change'] = $language;

        // loop through the tables and change language
        $dumpLang = new Db();
        $dumpLang->updateTable($conf, $path, $verbose);

        $aliases = [
          'original' => $to_clone['drush']['alias'],
          'clone' => $drush_alias,
         ];


        $clone = $sites["$clean_site_name"];
        $original = $to_clone;

        $rsync_files = new Rsync();
        $rsync_files->multi($original, $clone, $verbose);

        $rebuild = new Cache();
        $rebuild->rebuild($config, $verbose);

        return 0;

        // sql query för att få ut alla tabeller med langcode
        // drush sql-query "SELECT TABLE_NAME  FROM INFORMATION_SCHEMA.COLUMNS  WHERE COLUMN_NAME LIKE 'langcode' AND TABLE_SCHEMA='mikketysk'" > filetxt
        // loopa igenom filen
        // drush sql-query "UPDATE poll set langcode='sv'"

        // drush locale-import files/translations/drupal-8.4.5.sv sv
        // drush upwd admin --password=admin
    }
}

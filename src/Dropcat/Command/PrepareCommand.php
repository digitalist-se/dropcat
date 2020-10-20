<?php

namespace Dropcat\Command;

use Dropcat\Lib\DatabaseCommand;
use Dropcat\Lib\DropcatCommand;
use Dropcat\Lib\CheckDrupal;
use Dropcat\Lib\Tracker;
use Dropcat\Lib\Db;
use Dropcat\Lib\Write;
use Dropcat\Lib\Upload;
use Dropcat\Lib\Create;
use Dropcat\Lib\Vhost;
use Dropcat\Lib\Install;
use Dropcat\Lib\Config;
use Dropcat\Lib\RemotePath;
use Dropcat\Lib\Cleanup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Exception;
use Dropcat\Lib\UUID;
use Dropcat\Lib\Name;
use Symfony\Component\Process\Process;

class PrepareCommand extends DatabaseCommand
{

    protected function configure()
    {
        $HelpText = 'The <info>prepare</info> command setups what is needed for a site.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml):
<info>dropcat prepare</info>
To override config in dropcat.yml, using options:
<info>dropcat prepare --ssh_port=2200 --drush-alias=mysite</info>';

        $this->setName('prepare')
            ->setDescription('Prepare site')
            ->setDefinition(
                [
                    new InputOption(
                        'drush-folder',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Drush folder',
                        $this->configuration->localEnvironmentDrushFolder()
                    ),
                    new InputOption(
                        'drush-script',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Drush script path (can be remote)'
                    ),
                    new InputOption(
                        'drush-alias',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Drush alias',
                        $this->configuration->siteEnvironmentDrushAlias()
                    ),
                    new InputOption(
                        'server',
                        's',
                        InputOption::VALUE_OPTIONAL,
                        'Server',
                        $this->configuration->remoteEnvironmentServerName()
                    ),
                    new InputOption(
                        'user',
                        'u',
                        InputOption::VALUE_OPTIONAL,
                        'User (ssh)',
                        $this->configuration->remoteEnvironmentSshUser()
                    ),
                    new InputOption(
                        'ssh-port',
                        'p',
                        InputOption::VALUE_OPTIONAL,
                        'SSH port',
                        $this->configuration->remoteEnvironmentSshPort()
                    ),
                    new InputOption(
                        'ssh-key-password',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'SSH key password',
                        $this->configuration->localEnvironmentSshKeyPassword()
                    ),
                    new InputOption(
                        'ssh-key',
                        'i',
                        InputOption::VALUE_OPTIONAL,
                        'SSH key',
                        $this->configuration->remoteEnvironmentIdentifyFile()
                    ),
                    new InputOption(
                        'web-root',
                        'w',
                        InputOption::VALUE_OPTIONAL,
                        'Web root',
                        $this->configuration->remoteEnvironmentWebRoot()
                    ),
                    new InputOption(
                        'alias',
                        'a',
                        InputOption::VALUE_OPTIONAL,
                        'Symlink alias',
                        $this->configuration->remoteEnvironmentAlias()
                    ),
                    new InputOption(
                        'url',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Site url',
                        $this->configuration->siteEnvironmentUrl()
                    ),
                    new InputOption(
                        'site-name',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Site name',
                        $this->configuration->siteEnvironmentName()
                    ),
                    new InputOption(
                        'mysql-host',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Mysql host',
                        $this->configuration->mysqlEnvironmentHost()
                    ),
                    new InputOption(
                        'mysql-port',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Mysql port',
                        $this->configuration->mysqlEnvironmentPort()
                    ),
                    new InputOption(
                        'mysql-db',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Mysql db',
                        $this->configuration->mysqlEnvironmentDataBase()
                    ),
                    new InputOption(
                        'mysql-user',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Mysql user',
                        $this->configuration->mysqlEnvironmentUser()
                    ),
                    new InputOption(
                        'mysql-password',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Mysql password',
                        $this->configuration->mysqlEnvironmentPassword()
                    ),
                    new InputOption(
                        'timeout',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Timeout',
                        $this->configuration->timeOut()
                    ),
                    new InputOption(
                        'tracker-file',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Trackerfile',
                        $this->configuration->trackerFile()
                    ),
                    new InputOption(
                        'create-site',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Create site',
                        $this->configuration->createSite()
                    ),
                    new InputOption(
                        'sync-folder',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Sync folder',
                        $this->configuration->syncFolder()
                    ),
                    new InputOption(
                        'config-split-folder',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Config split folder',
                        $this->configuration->configSplitFolder()
                    ),
                    new InputOption(
                        'profile',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Install profile to use',
                        $this->configuration->drupalInstallProfile()
                    ),
                    new InputOption(
                        'tracker-dir',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Tracker direcory',
                        $this->configuration->trackerDir()
                    ),
                    new InputOption(
                        'backup-path',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Backup path',
                        $this->configuration->siteEnvironmentBackupPath()
                    ),
                    new InputOption(
                        'backup-db-path',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Backup DB path (absolute path with filename)',
                        $this->configuration->siteEnvironmentBackupDbPath()
                    ),
                    new InputOption(
                        'lang',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Language',
                        'en'
                    ),
                    new InputOption(
                        'config-split-settings',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Config split settings to use',
                        null
                    ),
                    new InputOption(
                        'server-alias',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Server alias',
                        null
                    ),
                    new InputOption(
                        'keep-drush-alias',
                        null,
                        InputOption::VALUE_NONE,
                        'do no overwrite drush alias'
                    ),
                    new InputOption(
                        'vhost-target',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Where to create vhost (multi)',
                        $this->configuration->vhostTarget()
                    ),
                    new InputOption(
                        'vhost-bash-command',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Command to run on vhost creation',
                        $this->configuration->vhostBashCommand()
                    ),
                    new InputOption(
                        'no-partial',
                        null,
                        InputOption::VALUE_NONE,
                        'do no use partial'
                    ),
                    new InputOption(
                        'no-email',
                        null,
                        InputOption::VALUE_NONE,
                        'do no send mail'
                    ),
                    new InputOption(
                        'location',
                        'o',
                        InputOption::VALUE_OPTIONAL,
                        "Where to save the drush alias file.",
                        NULL
                    ),
                ]
            )
            ->setHelp($HelpText);
    }

    /**
     * Helper function to create directories.
     *
     * @param string $path
     * @param int $timeout
     * @return array
     */
    protected function _makeDirectory(string $path, int $timeout = 10) {
        $command = ['mkdir', '-p', "$path"];

        $mkdir = $this->runProcess($command);
        $mkdir->setTimeout($timeout);
        $mkdir->mustRun();
        // Executes after the command finishes.
        if (!$mkdir->isSuccessful()) {
            return [
                'exitCode' => $mkdir->getExitCode(),
                'output' => $mkdir->getErrorOutput()
            ];
        }

        return [
            'exitCode' => $mkdir->getExitCode(),
            'output' => $mkdir->getOutput()
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drush_script = $input->getOption('drush-script');
        $drush_folder = $input->getOption('drush-folder');
        $drush_alias = $input->getOption('drush-alias');
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $ssh_port = $input->getOption('ssh-port');
        $identity_file = $input->getOption('ssh-key');
        $ssh_key_password = $input->getOption('ssh-key-password');
        $web_root = $input->getOption('web-root');
        $alias = $input->getOption('alias');
        $url = $input->getOption('url');
        $site_name = $input->getOption('site-name');
        $mysql_host = $input->getOption('mysql-host');
        $mysql_port = $input->getOption('mysql-port');
        $mysql_db = $input->getOption('mysql-db');
        $mysql_user = $input->getOption('mysql-user');
        $mysql_password = $input->getOption('mysql-password');
        $timeout = $input->getOption('timeout');
        $tracker_file = $input->getOption('tracker-file');
        $create_site = $input->getOption('create-site');
        $sync_folder = $input->getOption('sync-folder');
        $config_split_folder = $input->getOption('config-split-folder');
        $profile = $input->getOption('profile');
        $tracker_dir = $input->getOption('tracker-dir');
        $backup_path = $input->getOption('backup-path');
        $db_dump_path = $input->getOption('backup-db-path');
        $lang = $input->getOption('lang');
        $config_split_settings = $input->getOption('config-split-settings');
        $server_alias = $input->getOption('server-alias');
        $keep_drush_alias = $input->getOption('keep-drush-alias') ? true : false;
        $vhost_target = $input->getOption('vhost-target');
        $vhost_bash_command = $input->getOption('vhost-bash-command');
        $no_partial = $input->getOption('no-partial') ? true : false;
        $no_email = $input->getOption('no-email') ? true : false;
        $drush_memory_limit = $this->configuration->remoteEnvironmentDrushMemoryLimit();
        $identityFile = $this->configuration->remoteEnvironmentIdentifyFile();
        $location = $input->getOption('location');
        $env = $input->getParameterOption([
            '--env',
            '-e',
        ], getenv('DROPCAT_ENV') ?: 'dev');


        $output->writeln('<info>' . $this->start . ' prepare started</info>');
        $verbose = false;
        if ($output->isVerbose()) {
            $verbose = true;
        }

        // set need variables.
        $app_name = $this->configuration->localEnvironmentAppName();
        $mysql_root_user = $mysql_user;
        $mysql_root_pass = $mysql_password;
        $new_site_name = '';
        $site_alias = "$web_root/$alias";
        if (!isset($db_dump_path) || empty($db_dump_path)) {
            $db_dump_path = getenv('DB_DUMP_PATH');
            if (!isset($db_dump_path) || empty($db_dump_path)) {
                throw new Exception('you need to set the DB_DUMP_PATH variable or add the backup-db-path option');
            }
        }

        $backups_dir = substr($db_dump_path, 0, strrpos($db_dump_path, '/'));

        $server_time = date("Ymd_His");

        // @todo this variable usage doesn't really make sense
        if (!isset($db_dump_path)) {
            $db_dump_path = $backups_dir . '/' . $server_time . '.sql';
        }

        // Create backup dir if it does not exist.
        $res = $this->_makeDirectory($backups_dir);
        if ($res['exitCode'] === 0) {
            $output->writeln("<comment>Created  tracker dir at $backups_dir</comment>",
                OutputInterface::VERBOSITY_VERBOSE);
        } else {
            throw new Exception("Could not create tracker dir at $backups_dir", 1);
        }

        $default_tracker_conf = [
            'sites' => [
                'default' => [
                    'db' => [
                        'name' => $mysql_db,
                        'user' => $mysql_user,
                        'pass' => $mysql_password,
                        'host' => $mysql_host,
                    ],
                    'web' => [
                        'host' => $server,
                        'user' => $user,
                        'port' => $ssh_port,
                        'id-file' => $identity_file,
                        'pass' => $ssh_key_password,
                        'alias-path' => $site_alias,
                    ],
                    'drush' => [
                        'alias' => $drush_alias,
                    ]
                ],
            ],
        ];

        // Write the default tracker.
        if (isset($create_site)) {
            $multi = true;
        } else {
            $multi = false;
        }
        $write = new Tracker($verbose);
        $write->addDefault($default_tracker_conf, $app_name, $tracker_dir, $multi, $env);

        // Use the $create_site variable for setting up.
        if (isset($create_site)) {
            if ($tracker_file == null) {
                $tracker_file = $tracker_dir . '/default/' . $app_name . '-' . $env . '.yml';
            }

            $tracker = new Tracker($verbose);
            $sites = $tracker->read($tracker_file);

            foreach ($sites as $site => $siteProperty) {
                // getting the default user - same as the root user.
                if ($site === 'default') {
                    $mysql_host = $siteProperty['db']['host'];
                    $mysql_root_user = $siteProperty['db']['user'];
                    $mysql_root_pass = $siteProperty['db']['pass'];
                }

                if (isset($create_site)) {
                    $new_site_name = Name::site($create_site);
                    // check if a site already exists with that name.
                    if ($site === $new_site_name) {
                        throw new Exception("site $new_site_name already exists");
                    }
                }
            }

            $fixed_name = mb_strimwidth($new_site_name, 0, 64);
            $mysql_user = mb_strimwidth($new_site_name, 0, 32);
            $new_site_name = mb_strimwidth($new_site_name, 0, 32);
            $site_domain = $create_site;
            $drush_alias = $fixed_name;
            $mysql_db = $fixed_name;
            $mysql_password = uniqid();

            $mysql_conf = [
                'mysql-root-user' => $mysql_root_user,
                'mysql-root-pass' => $mysql_root_pass,
                'mysql-host' => $mysql_host,
                'mysql-user' => $mysql_user,
                'mysql-password' => $mysql_password,
                'timeout' => $timeout,
            ];

            $db = new Db($verbose);
            $db->createUser($mysql_conf);

            $new_db_conf = [
                'mysql-host' => $mysql_host,
                'mysql-user' => $mysql_user,
                'mysql-password' => $mysql_password,
                'mysql-db' => $mysql_db,
                'mysql-port' => $mysql_port,
                'timeout' => $timeout,
                'mysql-root-user' => $mysql_root_user,
                'mysql-root-pass' => $mysql_root_pass,
            ];
            // Create database.
            $db = new Db($verbose);
            $db->createDb($new_db_conf);

            $site_name = $drush_alias;
            $url = 'http://' . $create_site;
            $site_alias = "$web_root/$alias";
            $uuid = UUID::v4();
            $hash = hash('ripemd320', $uuid);
            $url_safe_hash = str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                $hash
            );

            $site = [
                $site_name => [
                    'db' => [
                        'dump' => null,
                        'name' => $mysql_db,
                        'user' => $mysql_user,
                        'pass' => $mysql_password,
                        'host' => $mysql_host,
                    ],
                    'web' => [
                        'host' => $server,
                        'hash' => $url_safe_hash,
                        'user' => $user,
                        'port' => $ssh_port,
                        'id-file' => $identity_file,
                        'pass' => $ssh_key_password,
                        'alias-path' => $site_alias,
                        'site-domain' => $site_domain,
                        'sync-folder' => $sync_folder,
                        'config-split-folder' => $config_split_folder,
                    ],
                    'drush' => [
                        'alias' => $drush_alias,
                    ]
                ],
            ];

            if (isset($server_alias)) {
                $site["$site_name"]['web']['server-alias'] = $server_alias;
            }

            $tracker_conf = [
                'tracker-file' => $tracker_file,
                'new-site' => $site,
                'tracker-dir' => $tracker_dir,
                'app-name' => $app_name,
            ];

            $write = new Tracker($verbose);
            $write->addMulti($tracker_conf);

            if ($keep_drush_alias === false) {
                // Create drush alias, if it is drupal.
                $check = new CheckDrupal();
                if ($check->isDrupal()) {
                    $drush_alias_conf = [
                        'env' => $env,
                        'drush-alias-name' => $drush_alias,
                        'site-name' => $site_name,
                        'server' => $server,
                        'user' => $user,
                        'web-root' => $web_root,
                        'alias' => $alias,
                        'url' => $url,
                        'ssh-port' => $ssh_port,
                        'drush-memory-limit' => $drush_memory_limit,
                        'location' => $location,
                        'identityFile' => $identityFile,
                    ];
                    $write = new Write();
                    $write->drushAlias($drush_alias_conf, $verbose);
                }
            }
            $sites_php_conf = [
                'app-name' => $app_name,
                'tracker-file' => $tracker_file,
            ];

            $sitesphp = new Write();
            $sitesphp->sitesPhp($sites_php_conf);

            //echo $tracker_file;

            $conf = [
                'tracker-file' => $tracker_file,
                'site' => $site_name,
                'app-name' => $app_name,
            ];
            $localSettings = new Write();
            $localSettings->localSettingsPhpMulti($conf);


            $target = $site_alias . '/web/sites/' . $site_domain;

            $remote_config = [
                'server' => $server,
                'user' => $user,
                'port' => $ssh_port,
                'key' => $identity_file,
                'pass' => $ssh_key_password,
                'timeout' => $timeout,
                'target' => $target,
            ];

            $create = new Create();
            $create->folder($remote_config);

            if (isset($config_split_folder)) {
                $remote_config['target'] = $site_alias . '/web/sites/' . $site_domain . '/sync';
                $create = new Create();
                $create->folder($remote_config);
                $output->writeln('<info>' . $this->mark . ' config split folder created.</info>');
            }

            $from = "/tmp/$app_name.local.settings.php";
            $to = "$target/settings.local.php";
            $upload_settings_local = new Upload();
            $upload_settings_local->place($remote_config, $from, $to, $verbose);


            if (file_exists("/tmp/$app_name-sites.php")) {
                $from = "/tmp/$app_name-sites.php";
                $to = $site_alias . '/web/sites/sites.php';
                $upload_sites_php = new Upload();
                $upload_sites_php->place($remote_config, $from, $to, $verbose);
            }

            $from = realpath("settings/default.settings.php");
            $to = "$target/settings.php";
            $upload_settings_php = new Upload();
            $upload_settings_php->place($remote_config, $from, $to, $verbose);

            $from = realpath("settings/default.services.yml");
            $to = "$target/services.yml";
            $upload_settings_php = new Upload();
            $upload_settings_php->place($remote_config, $from, $to, $verbose);

            $output->writeln('<info>' . $this->mark . ' needed files in place.</info>');

            // add option for this, now hardcoded
            $target = $vhost_target;
            $extra = '';
            $port = '80';
            $bash_command = $vhost_bash_command;

            $vhost_config = [
                'target' => $target,
                'file-name' => $new_site_name,
                'document-root' => "$site_alias/web",
                'port' => $port,
                'server-name' => $site_domain,
                'extra' => $extra,
                'bash-command' => $bash_command,
                'server' => $server,
                'user' => $user,
                'ssh-port' => $ssh_port,
                'ssh-key-password' => $ssh_key_password,
                'identity-file' => $identity_file,
            ];
            if (isset($server_alias)) {
                $vhost_config['server-alias'] = $server_alias;
            }

            // Create a vhost file.
            $vhost = new Vhost();
            $vhost->create($vhost_config);

            // If it is drupal, install the site.
            $check = new CheckDrupal();
            if ($check->isDrupal()) {
                $drush_config = [
                    'drush-alias' => $drush_alias,
                    'profile' => $profile,
                    'site-name' => $site_name,
                    'subdir' => $site_domain,
                    'no-email' => $no_email,
                ];

                $install = new Install();
                $install->drupal($drush_config, $lang, $verbose);

                $import = new Config();
                if ($no_partial == false) {
                    $import->importPartial($drush_config, $verbose);
                }

                if (isset($config_split_settings)) {
                    $export = new Config();
                    $export->configSplitExport($drush_config, $config_split_settings, $verbose);
                }

                $import = new Config();
                $import->import($drush_config, $verbose);
            }
        } // Create site ends.
        // Normal setup for a site.
        else {
            if (!isset($tracker_dir)) {
                throw new Exception('you need a tracker dir defined');
            }

            // write drush alias.
            if ($keep_drush_alias === false) {
                $check = new CheckDrupal();
                if ($check->isDrupal()) {
                    $drush_alias_conf = [
                        'env' => $env,
                        'drush-alias-name' => $drush_alias,
                        'site-name' => $site_name,
                        'server' => $server,
                        'user' => $user,
                        'web-root' => $web_root,
                        'alias' => $alias,
                        'url' => $url,
                        'ssh-port' => $ssh_port,
                        'drush-memory-limit' => $drush_memory_limit,
                        'location' => $location,
                        'identityFile' => $identityFile,
                    ];
                    $write = new Write();
                    $write->drushAlias($drush_alias_conf, $verbose);
                }
            }

            // Create database if it does not exist.
            $new_db_conf = [
                'drush_alias' => $drush_alias,
                'server' => $server,
                'user' => $user,
                'identityFile' => $identityFile,
                'mysql-host' => $mysql_host,
                'mysql-user' => $mysql_user,
                'mysql-password' => getenv('MYSQL_PASSWORD'),
                'mysql-db' => $mysql_db,
                'mysql-port' => $mysql_port,
                'timeout' => $timeout,
                'mysql-root-user' => getenv('MYSQL_ROOT_USER'),
                'mysql-root-pass' => getenv('MYSQL_ROOT_PASSWORD'),
            ];
            if (isset($db_dump_path)) {
                $new_db_conf['db-dump-path'] = $db_dump_path;
            }

            $this->databaseService->createDb($drush_alias, $new_db_conf);

            // Write rollback tracker.

            $build_tracker_conf = $default_tracker_conf;

            $build_id = getenv('BUILD_ID');
            if (!isset($build_id)) {
                $build_id = $server_time;
            }

            $build_tracker_dir = "$tracker_dir" . '/' . "$app_name" . '/';
            $build_tracker_file_name = $build_tracker_dir . $app_name . '-' . $env . '_' . "$build_id.yml";

            $res = $this->_makeDirectory($build_tracker_dir);
            if ($res['exitCode'] === 0) {
                $output->writeln("<comment>Created  tracker dir at $build_tracker_dir</comment>",
                    OutputInterface::VERBOSITY_VERBOSE);
            } else {
                throw new Exception("Could not create tracker dir at $build_tracker_dir", 1);
            }

            $web_server_conf = [
                'server' => $server,
                'user' => $user,
                'port' => $ssh_port,
                'pass' => $ssh_key_password,
                'key' => $identity_file,
                'alias' => $alias,
                'web-root' => $web_root,
            ];
            $get_site_path = new RemotePath($verbose);
            $real_path = $get_site_path->siteRealPath($web_server_conf);

            if (isset($real_path)) {
                $build_tracker_conf['sites']['default']['web']['site-path'] = $real_path;
            }
            $build_tracker_conf['created'] = $server_time;

            if (isset($build_id)) {
                $build_tracker_conf['build-id'] = $build_id;
            }
            $build_tracker_conf['db']['db-dump-path'] = $db_dump_path;

            $build_tracker = new Tracker($verbose);
            $build_tracker->rollback($build_tracker_conf, $build_tracker_file_name);
            $output->writeln('<info>' . $this->mark . ' created a rollback tracker file.</info>');

            $clean = new Cleanup();
            $clean->deleteOldRollbackTrackers($build_tracker_dir);
            $output->writeln('<info>' . $this->mark . ' deleted old rollback tracker files.</info>');

            $db_dump_dir = $backups_dir . "/";

            $clean = new Cleanup();
            $clean->deleteAutomaticDbBackups($db_dump_dir);
            $output->writeln('<info>' . $this->mark . ' deleted old automatic db backups.</info>');
        }
        $output->writeln('<info>' . $this->heart . ' prepare finished</info>');

        return 0;
    }
}

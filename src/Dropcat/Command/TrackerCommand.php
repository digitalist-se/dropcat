<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 *
 */
class TrackerCommand extends DropcatCommand
{

    /**
     *
     */
    protected function configure()
    {
        $HelpText = '<info>tracker</info> tracks settings set to the tracker.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat tracker</info>
To override config in dropcat.yml, using options:
<info>dropcat move --tracker-dir=/my/dir</info>';
        $this->setName("tracker")
          ->setDescription("Tracks configuration")
        ->setDefinition(
            [
            new InputOption(
                'app-name',
                null,
                InputOption::VALUE_OPTIONAL,
                'App name',
                $this->configuration->localEnvironmentAppName()
            ),
            new InputOption(
                'tracker-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'Tracker direcory',
                $this->configuration->trackerDir()
            ),
            new InputOption(
                'db-dump',
                null,
                InputOption::VALUE_OPTIONAL,
                'Complete path to db backup',
                $this->configuration->trackerDbDump()
            ),
            new InputOption(
                'db-name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database name.',
                $this->configuration->trackerDbName()
            ),
            new InputOption(
                'db-user',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database name.',
                $this->configuration->trackerDbUser()
            ),
            new InputOption(
                'db-pass',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database password.',
                $this->configuration->trackerDbPass()
            ),
            new InputOption(
                'db-host',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database host.',
                $this->configuration->trackerDbHost()
            ),
            new InputOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Id of tracker',
                $this->configuration->trackerId()
            ),
            new InputOption(
                'site-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to site',
                $this->configuration->trackerSitePath()
            ),
            new InputOption(
                'web-root',
                null,
                InputOption::VALUE_OPTIONAL,
                'Web root',
                $this->configuration->remoteEnvironmentWebRoot()
            ),
            new InputOption(
                'alias',
                null,
                InputOption::VALUE_OPTIONAL,
                'Symlink alias',
                $this->configuration->remoteEnvironmentAlias()
            ),
            new InputOption(
                'drush-alias',
                null,
                InputOption::VALUE_OPTIONAL,
                'Drush alias',
                $this->configuration->getFullDrushAlias()
            ),
              ]
        )
          ->setHelp($HelpText);
    }

    /**
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $app_name         = $input->getOption('app-name');
        $tracker_dir      = $input->getOption('tracker-dir');
        $id               = $input->getOption('id');
        $db_dump          = $input->getOption('db-dump');
        $db_name          = $input->getOption('db-name');
        $db_user          = $input->getOption('db-user');
        $db_pass          = $input->getOption('db-pass');
        $db_host          = $input->getOption('db-host');
        $site_path        = $input->getOption('site-path');
        $web_root         = $input->getOption('web-root');
        $alias            = $input->getOption('alias');
        $drush_alias      = $input->getOption('drush-alias');


        if (!isset($site_path)) {
            $site_path = $this->getSitePath();
        }

        $site_alias = "$web_root/$alias";

        try {
            if (!isset($tracker_dir)) {
                throw new Exception('tracker dir must be set');
            }
        } catch (\Exception $e) {
            echo 'error ' . $e->getMessage();
        }

        try {
            if (!isset($id)) {
                throw new Exception('tracker id must be set');
            }
        } catch (\Exception $e) {
            echo 'error ' . $e->getMessage();
        }


        // Dir for deploy tracker.
        $dir = "$tracker_dir/$app_name";
        // Default dir, this is for track sites.
        $default_dir = "$tracker_dir/default";
        // Create track directory.
        $this->writeDir($dir);
        // Create default dir
        $this->writeDir($default_dir);

        if (!isset($db_name)) {
            $db_name = $this->configuration->mysqlEnvironmentDataBase();
        }
        if (!isset($db_user)) {
            $db_user = $this->configuration->mysqlEnvironmentUser();
        }
        if (!isset($db_pass)) {
            $db_pass = $this->configuration->mysqlEnvironmentPassword();
        }
        if (!isset($db_host)) {
            $db_host = $this->configuration->mysqlEnvironmentHost();
        }
        $web_host = $this->configuration->remoteEnvironmentServerName();
        $web_host_user = $this->configuration->remoteEnvironmentSshUser();
        $web_host_port = $this->configuration->remoteEnvironmentSshPort();
        $web_host_id_file = $this->configuration->remoteEnvironmentIdentifyFile();
        $web_host_pass = $this->configuration->localEnvironmentSshKeyPassword();

        // get server time to add to tracker file.
        $server_time = date("Y-m-d H:i:s");

        // Populate the yaml.

        $conf = [
            'sites' => [
                'default' => [
                  'db' => [
                    'dump' =>$db_dump,
                    'name' => $db_name,
                    'user' => $db_user,
                    'pass' => $db_pass,
                    'host' => $db_host,
                    ],
                  'web' => [
                    'host' => $web_host,
                    'user' => $web_host_user,
                    'port' => $web_host_port,
                    'id-file' => $web_host_id_file,
                    'pass' => $web_host_pass,
                    'alias-path' => $site_alias,
                  ],
                  'drush' => [
                      'alias' => $drush_alias,
                  ]
                ],
            ],
        ];
        // the default, this is overwritten in every deploy. could be used for
        // backup scripts or similar to track sites.
        array_filter($conf);

        $this->writeTracker($default_dir, $app_name, $conf);
        // add some variables that should be unique
        $conf['created'] = $server_time;
        $build_id = getenv('BUILD_ID');
        $conf['sites']['default']['web']['site-path'] = $site_path;
        if (isset($build_id)) {
            $conf['build-id'] = $build_id;
        }
        array_filter($conf);
        $this->writeTracker($dir, $id, $conf);
        $build_id = getenv('BUILD_ID');
        $output->writeln('<info>' . $this->mark .
          ' tracker finished</info>');

        return 0;
    }

    /**
     * Write Yaml file.
     *
     * @param $dir
     *   string
     * @param $id
     *   string
     * @param $conf
     *   array
     */
    private function writeTracker($dir, $id, $conf)
    {
        $file = new Filesystem();
        $yaml = Yaml::dump($conf, 4, 2);
        try {
            $file->dumpFile($dir . '/' . $id . '.yml', $yaml);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your file at " . $e->getPath();
        }
    }

    /**
     * Create tracker dir.
     *
     * @param $dir
     *   string
     */
    private function writeDir($dir)
    {
        $createTrackerDir = new Process(['mkdir', '-p', "$dir"]);
        $createTrackerDir->mustRun();
    }
    private function getSitePath()
    {
        $alias = $this->configuration->remoteEnvironmentAlias();
        $web_root = $this->configuration->remoteEnvironmentWebRoot();
        $remote_path = "$web_root/$alias";
        $server = $this->configuration->remoteEnvironmentServerName();
        $user = $this->configuration->remoteEnvironmentSshUser();
        $port = $this->configuration->remoteEnvironmentSshPort();
        $key = $this->configuration->remoteEnvironmentIdentifyFile();
        $identity_file_content = file_get_contents($key);
        $pass = $this->configuration->localEnvironmentSshKeyPassword();

        $ssh = new SSH2($server, $port);
        $ssh->setTimeout(999);
        $auth = new RSA();
        if (isset($pass)) {
            $auth->setPassword($pass);
        }
        $auth->loadKey($identity_file_content);

        try {
            $login = $ssh->login($user, $auth);
            if (!$login) {
                throw new Exception('Login Failed using ' . $key .
                  ' at port ' . $port . ' and user ' . $user . ' at ' . $server .
                  ' ' . $ssh->getLastError());
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        $get_real_path = $ssh->exec("readlink -f $remote_path");
        $path = str_replace(array("\r", "\n"), '', trim($get_real_path));
        //$basename = $ssh->exec("basename $get_real_path");

        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "Could not get path, error code $status\n";
            $ssh->disconnect();
            exit($status);
        }
        $ssh->disconnect();
        return $path;
    }
}

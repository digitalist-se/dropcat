<?php
namespace Dropcat\Services;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class AppConfiguration
 * @package Services
 *
 * Loads configuration file and return variable from matching method, + some
 * helper-methods for things to do with configuration-file.
 */
class Configuration extends DropcatConfigurationBase implements DropcatConfigurationInterface
{
    /**
     * AppConfiguration constructor.
     */
    public function __construct()
    {
        $input = new ArgvInput();
        $env = $input->getParameterOption(array('--env', '-e'), getenv('DROPCAT_ENV') ?: 'dev');
        $running_path = getcwd();
        if (file_exists($running_path . '/.dropcat') && is_dir($running_path . '/.dropcat')) {
            $running_path .= '/.dropcat';
        }
        if (file_exists($running_path . '/dropcat.yml')) {
            $default_config = Yaml::parse(
                file_get_contents($running_path . '/dropcat.yml')
            );
            $configs = $default_config;
        }
        // Check for environment dropcat file.
        $environment = '/dropcat.' . $env . '.yml';
        if (file_exists($running_path . $environment)) {
            $env_config = Yaml::parse(
                file_get_contents($running_path . $environment)
            );
            // Recreate configs if env. exists.
            if (isset($default_config)) {
                $configs = array_replace_recursive($default_config, $env_config);
            } else {
                $configs = $env_config;
            }
        }

        if (isset($configs)) {
            // Settings some defaults so isset is not needed for root values.
            $this->configuration['local'] = null;
            $this->configuration['local']['environment'] = null;
            $this->configuration['remote'] = null;
            $this->configuration['remote']['environment'] = null;

            $this->configuration = $configs;
        } else {
            $this->configuration = null;
        }
    }

    /**
     * Gets the app name.
     */
    public function localEnvironmentAppName()
    {
        if (isset($this->configuration['app_name'])) {
            return $this->configuration['app_name'];
        } else {
            return null;
        }
    }

    /**
     * Gets the absolute path of the actual app we want to deploy.
     */
    public function localEnvironmentAppPath()
    {
        if (isset($this->configuration['local']['environment']['app_path'])) {
            return $this->configuration['local']['environment']['app_path'];
        } else {
            return null;
        }
    }


    /**
     * Get build id, prefderable overriden with option.
     */
    public function localEnvironmentBuildId()
    {
        $buildId = null;
        $buildNumber = getenv('BUILD_NUMBER');
        $buildDate = getenv('BUILD_DATE');
        if (isset($buildNumber)) {
            $buildId = $buildNumber;
            if (isset($buildDate)) {
                $buildId .= '_' . $buildDate;
            }
        } elseif (isset($buildDate)) {
            $buildId = $buildDate;
        } elseif (isset($this->configuration['local']['environment']['build_id'])) {
            $buildId = $this->configuration['local']['environment']['build_id'];
        }
        return $buildId;
    }

    /**
     * Gets the absolute path of a tmp-folder in this environment.
     */
    public function localEnvironmentTmpPath()
    {
        if (isset($this->configuration['local']['environment']['tmp_path'])) {
            return $this->configuration['local']['environment']['tmp_path'];
        } else {
            return '/tmp';
        }
    }

    /**
     * Gets the separator in names if set, otherwise defaults to _.
     */
    public function localEnvironmentSeparator()
    {
        if (isset($this->configuration['local']['environment']['separator'])) {
            return $this->configuration['local']['environment']['separator'];
        } else {
            return '_';
        }
    }

    /**
     * Gets the db to import.
     */
    public function localEnvironmentDbImport()
    {
        if (isset($this->configuration['local']['environment']['db_import'])) {
            return $this->configuration['local']['environment']['db_import'];
        } else {
            return null;
        }
    }

    /**
     * Get name of tar to deploy.
     */
    public function localEnvironmentTarName()
    {
        if (isset($this->configuration['local']['environment']['tar_name'])) {
            return $this->configuration['local']['environment']['tar_name'];
        } else {
            return null;
        }
    }

    /**
     * Get name of dir that tar is in.
     */
    public function localEnvironmentTarDir()
    {
        if (isset($this->configuration['local']['environment']['tar_dir'])) {
            return $this->configuration['local']['environment']['tar_dir'];
        } else {
            return '/tmp';
        }
    }

    /**
     * Get ssh-key password
     */
    public function localEnvironmentSshKeyPassword()
    {
        if (isset($this->configuration['remote']['environment']['ssh_key_password'])) {
            return $this->configuration['remote']['environment']['ssh_key_password'];
        } else {
            return null;
        }
    }

    /**
     * Get path to drush folder.
     */
    public function localEnvironmentDrushFolder()
    {
        if (isset($this->configuration['local']['environment']['drush_folder'])) {
            return $this->configuration['local']['environment']['drush_folder'];
        } else {
            return null;
        }
    }

    /**
     * Get path to drush folder.
     */
    public function localEnvironmentRun()
    {
        if (isset($this->configuration['local']['environment']['run'])) {
            return $this->configuration['local']['environment']['run'];
        } else {
            return null;
        }
    }

    /**
     * Get server for backups.
     */
    public function localEnvironmentBackupServer()
    {
        if (isset($this->configuration['local']['environment']['remote_backup_server'])) {
            return $this->configuration['local']['environment']['remote_backup_server'];
        } else {
            return null;
        }
    }

    /**
     * Get port for server for backups.
     */
    public function localEnvironmentBackupServerPort()
    {
        if (isset($this->configuration['local']['environment']['remote_backup_server_port'])) {
            return $this->configuration['local']['environment']['remote_backup_server_port'];
        } else {
            return null;
        }
    }

    /**
     * Get user to login to server for backups.
     */
    public function localEnvironmentBackupServerUser()
    {
        if (isset($this->configuration['local']['environment']['remote_backup_server_user'])) {
            return $this->configuration['local']['environment']['remote_backup_server_user'];
        } else {
            return null;
        }
    }

    /**
     * Get path to backups
     */
    public function localEnvironmentBackupPath()
    {
        if (isset($this->configuration['local']['environment']['remote_backup_path'])) {
            return $this->configuration['local']['environment']['remote_backup_path'];
        } else {
            return null;
        }
    }
    /**
     * Get user to login to server for backups.
     */
    public function localEnvironmentBackupDbName()
    {
        if (isset($this->configuration['local']['environment']['remote_db_backup_name'])) {
            return $this->configuration['local']['environment']['remote_db_backup_name'];
        } else {
            return null;
        }
    }


    /**
     * Get remote server name.
     */
    public function remoteEnvironmentServerName()
    {
        if (isset($this->configuration['remote']['environment']['server'])) {
            return $this->configuration['remote']['environment']['server'];
        } else {
            return null;
        }
    }

    /**
     * Get ssh user.
     */
    public function remoteEnvironmentSshUser()
    {
        if (isset($this->configuration['remote']['environment']['ssh_user'])) {
            return $this->configuration['remote']['environment']['ssh_user'];
        } else {
            return null;
        }
    }

    /**
     * Get ssh user.
     */
    public function remoteEnvironmentTargetPath()
    {
        if (isset($this->configuration['remote']['environment']['target_path'])) {
            return $this->configuration['remote']['environment']['target_path'];
        } else {
            return null;
        }
    }

    /**
     * Get ssh user.
     */
    public function remoteEnvironmentSshPort()
    {
        if (isset($this->configuration['remote']['environment']['ssh_port'])) {
            return $this->configuration['remote']['environment']['ssh_port'];
        } else {
            return null;
        }
    }

    /**
     * Get ssh pub key.
     */
    public function remoteEnvironmentIdentifyFile()
    {
        if (isset($this->configuration['remote']['environment']['identity_file'])) {
            return $this->configuration['remote']['environment']['identity_file'];
        } else {
            return null;
        }
    }

    /**
     * Get ssh web root.
     */
    public function remoteEnvironmentWebRoot()
    {
        if (isset($this->configuration['remote']['environment']['web_root'])) {
            return $this->configuration['remote']['environment']['web_root'];
        } else {
            return null;
        }
    }

    /**
     * Get remote temp folder.
     */
    public function remoteEnvironmentTempFolder()
    {
        if (isset($this->configuration['remote']['environment']['temp_folder'])) {
            return $this->configuration['remote']['environment']['temp_folder'];
        } else {
            return '/tmp';
        }
    }

    /**
     * Get environment alias.
     */
    public function remoteEnvironmentAlias()
    {
        if (isset($this->configuration['remote']['environment']['alias'])) {
            return $this->configuration['remote']['environment']['alias'];
        } else {
            return null;
        }
    }

    /**
     * Get upload target dir.
     */
    public function remoteEnvironmentTargetDir()
    {
        if (isset($this->configuration['remote']['environment']['target_dir'])) {
            return $this->configuration['remote']['environment']['target_dir'];
        } else {
            return '/tmp';
        }
    }

    /**
     * Get command, script to run remote.
     */

    public function remoteEnvironmentRun()
    {
        return $this->configuration['remote']['environment']['run'] ?? null;
    }

    /**
     * Gets the drush alias.
     */
    public function siteEnvironmentDrushAlias()
    {
        $name = $this->configuration['site']['environment']['name'] ?? null;
        $drushAlias = $this->configuration['site']['environment']['drush_alias'] ?? null;

        if ($drushAlias && $name) {
            return $name . '.' . $drushAlias;
        }

        return null;
    }

    /**
     * Gets site install drush extra options.
     */
    public function siteEnvironmentDrushInstallOptions()
    {
        return $this->configuration['site']['environment']['drush_install_options'] ?? null;
    }



    /**
     * Gets the sites backup path.
     */
    public function siteEnvironmentBackupPath()
    {
        if (isset($this->configuration['site']['environment']['backup_path'])) {
            return $this->configuration['site']['environment']['backup_path'];
        } else {
            return null;
        }
    }

    /**
     * Gets the sites backup path.
     */
    public function siteEnvironmentConfigName()
    {
        if (isset($this->configuration['site']['environment']['config_name'])) {
            return $this->configuration['site']['environment']['config_name'];
        } else {
            return null;
        }
    }

    /**
     * Gets the sites backup path.
     */
    public function siteEnvironmentOriginalPath()
    {
        if (isset($this->configuration['site']['environment']['original_path'])) {
            return $this->configuration['site']['environment']['original_path'];
        } else {
            return null;
        }
    }

    /**
     * Gets the sites backup path.
     */
    public function siteEnvironmentSymLink()
    {
        if (isset($this->configuration['site']['environment']['symlink'])) {
            return $this->configuration['site']['environment']['symlink'];
        } else {
            return null;
        }
    }

    /**
     * Gets the sites backup path.
     */
    public function siteEnvironmentUrl()
    {
        if (isset($this->configuration['site']['environment']['url'])) {
            return $this->configuration['site']['environment']['url'];
        } else {
             return null;
        }
    }

    /**
     * Gets the sites name.
     */
    public function siteEnvironmentName()
    {
        if (isset($this->configuration['site']['environment']['name'])) {
            return $this->configuration['site']['environment']['name'];
        } else {
            return null;
        }
    }


    /**
     * Get the profile name
     */
    public function siteEnvironmentProfile()
    {
        if (isset($this->configuration['site']['environment']['profile'])) {
            return $this->configuration['site']['environment']['profile'];
        } else {
            return null;
        }
    }

    /**
     * Gets the sites backup path.
     */
    public function siteEnvironmentBackupDbPath()
    {
        if (isset($this->configuration['site']['environment']['backup_db_path'])) {
            return $this->configuration['site']['environment']['backup_db_path'];
        } else {
            return null;
        }
    }
    /**
     * Gets all ignore-files from config-file.
     */
    public function deployIgnoreFiles()
    {
        if (isset($this->configuration['deploy']['ignore'])) {
            return $this->configuration['deploy']['ignore'];
        } else {
            return null;
        }
    }

    /**
    * Gets varnish IP from config-file.
    */
    public function deployVarnishIP()
    {
        if (isset($this->configuration['deploy']['varnish_ip'])) {
            return $this->configuration['deploy']['varnish_ip'];
        } else {
            return null;
        }
    }

    /**
    * Gets varnish port from config-file.
    */
    public function deployVarnishPort()
    {
        if (isset($this->configuration['deploy']['varnish_port'])) {
            return $this->configuration['deploy']['varnish_port'];
        } else {
            return null;
        }
    }

    /**
     * Gets Mysql/MariaDB host
     */
    public function mysqlEnvironmentHost()
    {
        if (isset($this->configuration['mysql']['environment']['host'])) {
            return $this->configuration['mysql']['environment']['host'];
        } else {
            return null;
        }
    }

    /**
     * Gets Mysql database
     */
    public function mysqlEnvironmentDataBase()
    {
        if (isset($this->configuration['mysql']['environment']['database'])) {
            return $this->configuration['mysql']['environment']['database'];
        } else {
            return null;
        }
    }

    /**
     * Gets Mysql user
     */
    public function mysqlEnvironmentUser()
    {
        if (isset($this->configuration['mysql']['environment']['user'])) {
            return $this->configuration['mysql']['environment']['user'];
        } else {
            return null;
        }
    }

    /**
     * Gets Mysql port
     */
    public function mysqlEnvironmentPort()
    {
        if (isset($this->configuration['mysql']['environment']['port'])) {
            return $this->configuration['mysql']['environment']['port'];
        } else {
            return null;
        }
    }

    /**
     * Gets Mysql port
     */
    public function mysqlEnvironmentPassword()
    {
        if (isset($this->configuration['mysql']['environment']['password'])) {
            return $this->configuration['mysql']['environment']['password'];
        } else {
            return null;
        }
    }

    /**
     * Gets Jenkins server
     */
    public function deployJenkinsServer()
    {
        if (isset($this->configuration['deploy']['jenkins_server'])) {
            return $this->configuration['deploy']['jenkins_server'];
        } else {
            return null;
        }
    }

    /**
     * Gets Jenkins job
     */
    public function deployJenkinsJob()
    {
        if (isset($this->configuration['deploy']['jenkins_job'])) {
            return $this->configuration['deploy']['jenkins_job'];
        } else {
            return null;
        }
    }


    /**
     * Get admin pass for site.
     */
    public function siteEnvironmentAdminPass()
    {
        if (isset($this->configuration['site']['environment']['admin_pass'])) {
            return $this->configuration['site']['environment']['admin_pass'];
        } else {
            $password = mt_rand();
            return $password;
        }
    }

    /**
     * Get admin pass for site.
     */
    public function siteEnvironmentAdminUser()
    {
        if (isset($this->configuration['site']['environment']['admin_user'])) {
            return $this->configuration['site']['environment']['admin_user'];
        } else {
            return 'admin';
        }
    }


    public function nodeNvmDirectory()
    {
        return $this->configuration['node']['nvm_directory'] ?? NULL;
    }

    public function nodeNvmRcFile() : string
    {
        if (isset($this->configuration['node']['nvmrc_file'])) {
            return $this->configuration['node']['nvmrc_file'];
        } else {
            return '';
        }
    }

    public function gulpDirectory()
    {
        if (isset($this->configuration['node']['gulp_directory'])) {
            return $this->configuration['node']['gulp_directory'];
        } else {
            return null;
        }
    }
    public function gulpOptions()
    {
        if (isset($this->configuration['node']['gulp_options'])) {
            return $this->configuration['node']['gulp_options'];
        } else {
            return '';
        }
    }
    public function nodeEnvironment()
    {
        if (isset($this->configuration['node']['environment'])) {
            $nodeEnvironment = $this->configuration['node']['environment'];
            return $nodeEnvironment;
        } else {
            return null;
        }
    }
    public function localEnvironmentRsyncFrom()
    {
        if (isset($this->configuration['local']['environment']['rsync_from'])) {
            $from = $this->configuration['local']['environment']['rsync_from'];
            return $from;
        } else {
            return null;
        }
    }
    public function remoteEnvironmentRsyncTo()
    {
        if (isset($this->configuration['remote']['environment']['rsync_to'])) {
            $from = $this->configuration['remote']['environment']['rsync_to'];
            return $from;
        } else {
            return null;
        }
    }
    /**
     * Get ssh port for local use.
     */
    public function remoteEnvironmentLocalSshPort()
    {
        if (isset($this->configuration['remote']['environment']['local_ssh_port'])) {
            return $this->configuration['remote']['environment']['local_ssh_port'];
        } else {
            return null;
        }
    }
    /**
     * Get server for local use.
     */
    public function remoteEnvironmentLocalServerName()
    {
        if (isset($this->configuration['remote']['environment']['local_server'])) {
            return $this->configuration['remote']['environment']['local_server'];
        } else {
            return null;
        }
    }
    /**
     * Get ssh user for local use.
     */
    public function remoteEnvironmentLocalSshUser()
    {
        if (isset($this->configuration['remote']['environment']['local_ssh_user'])) {
            return $this->configuration['remote']['environment']['local_ssh_user'];
        } else {
            return null;
        }
    }
    public function remoteEnvironmentRsyncFrom()
    {
        if (isset($this->configuration['remote']['environment']['rsync_from'])) {
            $from = $this->configuration['remote']['environment']['rsync_from'];
            return $from;
        } else {
            return null;
        }
    }
    public function localEnvironmentRsyncTo()
    {
        if (isset($this->configuration['local']['environment']['rsync_to'])) {
            $from = $this->configuration['local']['environment']['rsync_to'];
            return $from;
        } else {
            return null;
        }
    }
    public function vhostFileName()
    {
        if (isset($this->configuration['vhost']['file_name'])) {
            $file_name = $this->configuration['vhost']['file_name'];
            return $file_name;
        } else {
            return null;
        }
    }
    public function vhostTarget()
    {
        if (isset($this->configuration['vhost']['target'])) {
            $target = $this->configuration['vhost']['target'];
            return $target;
        } else {
            return '/etc/httpd/conf.d';
        }
    }
    public function vhostPort()
    {
        if (isset($this->configuration['vhost']['port'])) {
            $port = $this->configuration['vhost']['port'];
            return $port;
        } else {
            return '80';
        }
    }
    public function vhostDocumentRoot()
    {
        if (isset($this->configuration['vhost']['document_root'])) {
            $port = $this->configuration['vhost']['document_root'];
            return $port;
        } else {
            return null;
        }
    }
    public function vhostServerName()
    {
        if (isset($this->configuration['vhost']['server_name'])) {
            $server_name = $this->configuration['vhost']['server_name'];
            return $server_name;
        } else {
            return null;
        }
    }

    /**
     * Return extra config for vhost.
     */
    public function vhostBashCommand()
    {
        if (isset($this->configuration['vhost']['bash_command'])) {
            $bash_command = $this->configuration['vhost']['bash_command'];
            return $bash_command;
        } else {
            return 'sudo service httpd restart';
        }
    }


    /**
     * Gets all server-aliases.
     */
    public function getServerAliases()
    {
        if (isset($this->configuration['vhost']['server_alias'])) {
            return $this->configuration['vhost']['server_alias'];
        } else {
            return null;
        }
    }
    /**
     * Gets all server extras.
     */
    public function getServerExtras()
    {
        if (isset($this->configuration['vhost']['extra'])) {
            return $this->configuration['vhost']['extra'];
        } else {
            return null;
        }
    }

    /**
     * Directory to save tracker data.
     */
    public function trackerDir()
    {
        if (isset($this->configuration['tracker']['dir'])) {
            $dir = $this->configuration['tracker']['dir'];
            return $dir;
        } else {
            return null;
        }
    }
    /**
     * Absolute path to db dump.
     */
    public function trackerDbDump()
    {
        if (isset($this->configuration['tracker']['db-dump'])) {
            $db = $this->configuration['tracker']['db-dump'];
            return $db;
        } else {
            return null;
        }
    }
  /**
   * Db user.
   */
    public function trackerDbUser()
    {
        if (isset($this->configuration['tracker']['db-user'])) {
            $db = $this->configuration['tracker']['db-user'];
            return $db;
        } else {
            return null;
        }
    }

  /**
   * Db pass.
   */
    public function trackerDbPass()
    {
        if (isset($this->configuration['tracker']['db-pass'])) {
            $db = $this->configuration['tracker']['db-pass'];
            return $db;
        } else {
            return null;
        }
    }
  /**
   * Db pass.
   */
    public function trackerDbName()
    {
        if (isset($this->configuration['tracker']['db-name'])) {
            $db_name = $this->configuration['tracker']['db-name'];
            return $db_name;
        } else {
            return null;
        }
    }
  /**
   * Db host.
   */
    public function trackerDbHost()
    {
        if (isset($this->configuration['tracker']['db-host'])) {
            $db = $this->configuration['tracker']['db-host'];
            return $db;
        } else {
            return null;
        }
    }
    /**
     * Id of tracker.
     */
    public function trackerId()
    {
        if (isset($this->configuration['tracker']['id'])) {
            $id = $this->configuration['tracker']['id'];
            return $id;
        } else {
            return null;
        }
    }
    /**
     * site path to tracker.
     */
    public function trackerSitePath()
    {
        if (isset($this->configuration['tracker']['site-path'])) {
            $id = $this->configuration['tracker']['site-path'];
            return $id;
        } else {
            return null;
        }
    }
     /**
     * trackerfile ro use.
     */
    public function trackerFile()
    {
        if (isset($this->configuration['tracker']['file'])) {
            $id = $this->configuration['tracker']['file'];
            return $id;
        } else {
            return null;
        }
    }

    /**
     * rollback id.
     */
    public function rollbackId()
    {
        if (isset($this->configuration['rollback']['id'])) {
            $id = $this->configuration['rollback']['id'];
            return $id;
        } else {
            return null;
        }
    }
    /**
     * create site
     */
    public function createSite()
    {
            return null;
    }
    /**
     * Sync config name
     */
    public function syncFolder()
    {
        if (isset($this->configuration['site']['environment']['sync'])) {
            return $this->configuration['site']['environment']['sync'];
        } else {
            return '../sync';
        }
    }
    /**
     * Sync config name
     */
    public function configSplitFolder()
    {
        if (isset($this->configuration['site']['environment']['config-split'])) {
            return $this->configuration['site']['environment']['config-split'];
        } else {
            return null;
        }
    }

    /**
     * Drupal install profile to use, defaults to minimal.
     */
    public function drupalInstallProfile()
    {
        return 'minimal';
    }

    /**
     * Gets all settings overrides.
     */
    public function siteSettings()
    {
        if (isset($this->configuration['site']['settings'])) {
            return $this->configuration['site']['settings'];
        } else {
            return null;
        }
    }

    /**
     * Gets all config overrides.
     */
    public function siteConfig()
    {
        if (isset($this->configuration['site']['config'])) {
            return $this->configuration['site']['config'];
        } else {
            return null;
        }
    }

    /**
     * Get drush php memory
     */
    public function remoteEnvironmentDrushMemoryLimit()
    {
        if (isset($this->configuration['remote']['environment']['drush']['limit'])) {
            return '-d memory_limit=' . $this->configuration['remote']['environment']['drush']['limit'];
        } else {
            return '-d memory_limit=1024M';
        }
    }
}

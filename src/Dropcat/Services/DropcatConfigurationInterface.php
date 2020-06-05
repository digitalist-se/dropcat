<?php

namespace Dropcat\Services;

interface DropcatConfigurationInterface
{
  /**
   * Gets the app name.
   */
    public function localEnvironmentAppName();

  /**
   * Gets the absolute path of the actual app we want to deploy.
   */
    public function localEnvironmentAppPath();


  /**
   * Get build id, prefderable overriden with option.
   */
    public function localEnvironmentBuildId();

  /**
   * Gets the absolute path of a tmp-folder in this environment.
   */
    public function localEnvironmentTmpPath();

  /**
   * Gets the separator in names.
   */
    public function localEnvironmentSeparator();

  /**
   * Gets the db to import.
   */
    public function localEnvironmentDbImport();

  /**
   * Get name of tar to deploy.
   */
    public function localEnvironmentTarName();

  /**
   * Get name of dir that tar is in.
   */
    public function localEnvironmentTarDir();

  /**
   * Get ssh-key password
   */
    public function localEnvironmentSshKeyPassword();

  /**
   * Get path to drush folder.
   */
    public function localEnvironmentDrushFolder();

  /**
   * Get path to drush folder.
   */
    public function localEnvironmentRun();

  /**
   * Get server for backups.
   */
    public function localEnvironmentBackupServer();

  /**
   * Get port for server for backups.
   */
    public function localEnvironmentBackupServerPort();

  /**
   * Get user to login to server for backups.
   */
    public function localEnvironmentBackupServerUser();

  /**
   * Get path to backups
   */
    public function localEnvironmentBackupPath();

  /**
   * Get user to login to server for backups.
   */
    public function localEnvironmentBackupDbName();

  /**
   * Get remote server name.
   */
    public function remoteEnvironmentServerName();

  /**
   * Get ssh user.
   */
    public function remoteEnvironmentSshUser();

  /**
   * Get ssh user.
   */
    public function remoteEnvironmentTargetPath();

  /**
   * Get ssh user.
   */
    public function remoteEnvironmentSshPort();

  /**
   * Get ssh pub key.
   */
    public function remoteEnvironmentIdentifyFile();

  /**
   * Get ssh web root.
   */
    public function remoteEnvironmentWebRoot();

  /**
   * Get remote temp folder.
   */
    public function remoteEnvironmentTempFolder();

  /**
   * Get environment alias.
   */
    public function remoteEnvironmentAlias();

  /**
   * Get upload target dir.
   */
    public function remoteEnvironmentTargetDir();

  /**
   * Get command, script to run remote.
   */
    public function remoteEnvironmentRun();

  /**
   * Gets the drush alias.
   */
    public function siteEnvironmentDrushAlias();

  /**
   * Gets site install drush extra options.
   */
    public function siteEnvironmentDrushInstallOptions();

  /**
   * Gets the sites backup path.
   */
    public function siteEnvironmentBackupPath();

  /**
   * Gets the sites backup path.
   */
    public function siteEnvironmentConfigName();

  /**
   * Gets the sites backup path.
   */
    public function siteEnvironmentOriginalPath();

  /**
   * Gets the sites backup path.
   */
    public function siteEnvironmentSymLink();

  /**
   * Gets the sites backup path.
   */
    public function siteEnvironmentUrl();

  /**
   * Gets the sites name.
   */
    public function siteEnvironmentName();

  /**
   * Get the profile name
   */
    public function siteEnvironmentProfile();


  /**
   * Gets varnish IP from config-file.
   */
    public function deployVarnishIP();

  /**
   * Gets varnish port from config-file.
   */
    public function deployVarnishPort();

  /**
   * Gets Mysql/MariaDB host
   */
    public function mysqlEnvironmentHost();

  /**
   * Gets Mysql database
   */
    public function mysqlEnvironmentDataBase();

  /**
   * Gets Mysql user
   */
    public function mysqlEnvironmentUser();

  /**
   * Gets Mysql port
   */
    public function mysqlEnvironmentPort();

  /**
   * Gets Mysql port
   */
    public function mysqlEnvironmentPassword();

  /**
   * Gets Jenkins server
   */
    public function deployJenkinsServer();

  /**
   * Gets Jenkins job
   */
    public function deployJenkinsJob();

  /**
   * Get admin pass for site.
   */
    public function siteEnvironmentAdminPass();

  /**
   * Get admin pass for site.
   */
    public function siteEnvironmentAdminUser();

  /**
   * Gets all ignore-files formatted for tar-excluding.
   */
    public function deployIgnoreFilesTarString();


    public function nodeNvmDirectory();

    public function nodeNvmRcFile();

    public function gulpDirectory();

    public function gulpOptions();

    public function nodeEnvironment();

    public function localEnvironmentRsyncFrom();

    public function remoteEnvironmentRsyncTo();

  /**
   * Get ssh port for local use.
   */
    public function remoteEnvironmentLocalSshPort();

  /**
   * Get server for local use.
   */
    public function remoteEnvironmentLocalServerName();

  /**
   * Get ssh user for local use.
   */
    public function remoteEnvironmentLocalSshUser();

    public function remoteEnvironmentRsyncFrom();

    public function localEnvironmentRsyncTo();

    public function vhostFileName();

    public function vhostTarget();

    public function vhostPort();

    public function vhostDocumentRoot();

    public function vhostServerName();

  /**
   * Return extra config for vhost.
   */
    public function vhostBashCommand();

    /**
     * Path to ssh identity file on the local system.
     * @return string | null
     */
    public function localEnvironmentSshIdentityFile();
}

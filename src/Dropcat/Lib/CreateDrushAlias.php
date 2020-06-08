<?php
namespace Dropcat\Lib;

use Symfony\Component\Yaml\Yaml;

/**
 * Class CreateDrushAlias
 *
 * Checking if it is Drupal, and which version.
 *
 * @package Dropcat\Lib
 */


class CreateDrushAlias
{
    private $drushAliasName;
    private $env;
    private $siteName;
    private $server;
    private $user;
    private $webroot;
    private $sitePath;
    private $url;
    private $sshport;
    private $drushScript = null;
    private $drushMemoryLimit;
    private $location;
    private $identityFile;

    /**
     * @param string $env
     */
    public function setEnv($env): void {
        $this->env = $env;
    }

    /**
     * @param string $drushAliasName
     *
     * @return void
     */
    public function setDrushAliasName($drushAliasName): void {
        $this->drushAliasName = $drushAliasName;
    }

    public function setName($sitename)
    {
        $this->siteName = $sitename;
    }

    public function setServer($server)
    {
        $this->server = $server;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setWebRoot($webroot)
    {
        $this->webroot = $webroot;
    }

    public function setSitePath($sitePath)
    {
        $this->sitePath = $sitePath;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setSSHPort($sshport)
    {
        $this->sshport = $sshport;
    }

    public function setDrushScriptPath($script_path)
    {
        $this->drushScript = $script_path;
    }

    public function setDrushMemoryLimit($drushmemorylimit)
    {
        $this->drushMemoryLimit = $drushmemorylimit;
    }

    /**
     * @param string $location
     */
    public function setLocation($location): void {
        $this->location = $location;
    }

    /**
     * @param string $identityFile
     */
    public function setIdentityFile($identityFile): void {
        $this->identityFile = $identityFile;
    }

    /**
     * Get options for the drush alias.
     * @return array
     */
    public function getValue()
    {
        $options[$this->env] = [
          "options" => ['php-options' => $this->drushMemoryLimit],
          "host" => $this->server,
          "user" => $this->user,
          "root" => $this->webroot . '/' . $this->sitePath . '/web',
          "uri"  => $this->url,
          "ssh" => ['options' => '-o LogLevel=Error -q -p ' . $this->sshport],
        ];

        if ($this->identityFile) {
            $options[$this->env]['ssh']['options'] .= ' -i ' . $this->identityFile;
        }

        if ($this->drushScript) {
            $options[$this->env]['paths'] = [
              'drush-script' => $this->drushScript
            ];
        }

        return $options;
    }

    /**
     * Get options for the drush alias as YAML.
     * @return string
     */
    public function toYaml() {
        return Yaml::dump($this->getValue());
    }

  /**
   * Return the user's home directory.
   * Copied from drush_server_home().
   */
    public function drushServerHome()
    {
      // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
      // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
          // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        } elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
          // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
          // If HOMEPATH is a root directory the path can end with a slash. Make sure
          // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? null : $home;
    }
}

// Usage example
//
//$drushAlias = new CreateDrushAlias();
//$drushAlias->setName($siteName);
//$drushAlias->setServer($server);
//$drushAlias->setUser($user);
//$drushAlias->setWebRoot($webroot);
//$drushAlias->setSitePath($alias);
//$drushAlias->setUrl($url);
//$drushAlias->setSSHPort($sshport);
//
//$home = new CreateDrushAlias();
//$home_dir = $home->drushServerHome();
//
//$drush_alias_name = 'myalias';
//
//$drush_file = new Filesystem();
//
//try {
//  $drush_file->dumpFile($home_dir.'/.drush/'.$drush_alias_name.
//    '.aliases.drushrc.php', $drushAlias->getValue());
//} catch (IOExceptionInterface $e) {
//  echo 'An error occurred while creating your file at '.$e->getPath();
//}

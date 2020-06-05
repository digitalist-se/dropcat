<?php
namespace Dropcat\Lib;

/**
 * Class CheckDrupal
 *
 * Checking if it is Drupal, and which version.
 *
 * @package Dropcat\Lib
 */


class CreateDrushAlias
{
    private $sitename;
    private $server;
    private $user;
    private $webroot;
    private $alias;
    private $url;
    private $sshport;
    private $drushScript = null;
    private $drushMemoryLimit;
    private $sshIdentityOption = false;

    public function setName($sitename)
    {
        $this->sitename = $sitename;
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

    public function setSitePath($alias)
    {
        $this->alias = $alias;
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

    public function setSshIdentityOption($identityOption) {
        $this->sshIdentityOption = $identityOption;
    }


    public function getValue()
    {
        if (empty($this->sshIdentityOption)) {
            $aliasOut = '<?php
      $aliases["' . $this->sitename . '"] = array (
        "php-options" =>  "' . $this->drushMemoryLimit . '",
        "remote-host" => "' . $this->server . '",
        "remote-user" => "' . $this->user . '",
        "root" => "' . $this->webroot . '/' . $this->alias . '/web",
        "uri"  => "' . $this->url . '",
        "ssh-options" => "-o LogLevel=Error -q -p ' . $this->sshport . '",';
            if ($this->drushScript) {
                $aliasOut .= '
          "path-aliases" =>  array(
             "%drush-script"  => "' . $this->drushScript . '",
          ),';
            }
            $aliasOut .= ');';
        }
        else {
            $aliasOut = '<?php
  $aliases["' . $this->sitename . '"] = array (
    "php-options" =>  "' . $this->drushMemoryLimit . '",
    "remote-host" => "' . $this->server . '",
    "remote-user" => "' . $this->user . '",
    "root" => "' . $this->webroot . '/' . $this->alias . '/web",
    "uri"  => "' . $this->url . '",
    "ssh-options" => "-o LogLevel=Error -q -p ' . $this->sshport . ' -i ' . $this->sshIdentityOption . '",';
            if ($this->drushScript) {
                $aliasOut .= '
      "path-aliases" =>  array(
         "%drush-script"  => "'. $this->drushScript .'",
      ),';
            }
            $aliasOut .= ');';
        }


        return ($aliasOut);
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

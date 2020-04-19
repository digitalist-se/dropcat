<?php
namespace Dropcat\Lib;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use Exception;

/**
 * Class Remove
 *
 * remove something somewhere.
 *
 * @package Dropcat\Lib
 */
class Remove
{
    public $dir;
    public $fs;
    public $mark;

    public function __construct()
    {
        $this->fs = new Filesystem();
        $style = new Styles();
        $mark = $style->heavyCheckMark();
        $this->mark = $style->colorize('yellow', $mark);
        $this->output = new ConsoleOutput();
    }

    /**
     * Place a local file at remote destination.
     */
    public function file($config, $file, $verbose = false)
    {
        $server = $config['server'];
        $port = $config['port'];
        $user = $config['user'];
        $timeout = $config['timeout'];
        $ssh_key_password = $config['pass'];
        $identity_file = $config['key'];
        $identity_file_content = file_get_contents($identity_file);

        $sftp = new SFTP($server, $port, $timeout);
        $sftp->setTimeout(999);

        $auth = new RSA();
        if (isset($ssh_key_password)) {
            $auth->setPassword($ssh_key_password);
        }
        $auth->loadKey($identity_file_content);
        try {
            $login = $sftp->login($user, $auth);
            if (!$login) {
                throw new Exception('login Failed using ' . $identity_file . ' and user ' . $user . ' at ' . $server);
            }

            $delete = $sftp->delete($file, false);
            if (!$delete) {
                throw new Exception("Could not remove file at $file");
            }
        } catch (Exception $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            exit(1);
        }

        if ($verbose == true) {
            $this->output->writeln("<info>$this->mark $file deleted from $server</info>");
        }
    }
}

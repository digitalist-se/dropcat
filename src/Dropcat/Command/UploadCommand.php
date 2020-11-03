<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class UploadCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>upload</info> connects to remote server and upload tar and unpack it in path.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat upload</info>
To override config in dropcat.yml, using options:
<info>dropcat upload -server 127.0.0.0 -i my_pub.key</info>';

        $this->setName("site:upload")
            ->setAliases(["upload"])
          ->setDescription("Upload to server")
        ->setDefinition(
            array(
            new InputOption(
                'app-name',
                'a',
                InputOption::VALUE_OPTIONAL,
                'App name',
                $this->configuration->localEnvironmentAppName()
            ),
            new InputOption(
                'build-id',
                'bi',
                InputOption::VALUE_OPTIONAL,
                'Id',
                $this->configuration->localEnvironmentBuildId()
            ),
            new InputOption(
                'separator',
                'se',
                InputOption::VALUE_OPTIONAL,
                'Name separator',
                $this->configuration->localEnvironmentSeparator()
            ),
            new InputOption(
                'tar',
                't',
                InputOption::VALUE_OPTIONAL,
                'Tar',
                $this->configuration->localEnvironmentTarName()
            ),
            new InputOption(
                'tar_dir',
                'td',
                InputOption::VALUE_OPTIONAL,
                'Tar dir',
                $this->configuration->localEnvironmentTarDir()
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
                'ssh_port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'SSH port',
                $this->configuration->remoteEnvironmentSshPort()
            ),
            new InputOption(
                'ssh_key_password',
                'skp',
                InputOption::VALUE_OPTIONAL,
                'SSH key password',
                $this->configuration->localEnvironmentSshKeyPassword()
            ),
            new InputOption(
                'target_dir',
                'tp',
                InputOption::VALUE_OPTIONAL,
                'Target dir',
                $this->configuration->remoteEnvironmentTargetDir()
            ),
            new InputOption(
                'identity_file',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Identify file',
                $this->configuration->remoteEnvironmentIdentifyFile()
            ),
            new InputOption(
                'timeout',
                'to',
                InputOption::VALUE_OPTIONAL,
                'Timeout',
                $this->configuration->timeOut()
            ),
            new InputOption(
                'keeptar',
                'kt',
                InputOption::VALUE_NONE,
                'Keep tar after upload  (defaults to no)'
            ),
            new InputOption(
                'dontchecksha1',
                'dsha1',
                InputOption::VALUE_NONE,
                "Don't check SHA1 hash for file (defaults to no)"
            ),
              )
        )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app_name = $input->getOption('app-name');
        $build_id = $input->getOption('build-id');
        $separator = $input->getOption('separator');
        $tar = $input->getOption('tar');
        $tar_dir = $input->getOption('tar_dir');
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $targetdir = $input->getOption('target_dir');
        $port = $input->getOption('ssh_port');
        $ssh_key_password = $input->getOption('ssh_key_password');
        $identity_file = $input->getOption('identity_file');
        $identity_file_content = file_get_contents($identity_file);
        $timeout = $input->getOption('timeout');
        $keeptar = $input->getOption('keeptar') ? true : false;
        $checksha1 = $input->getOption('dontchecksha1') ? false : true;

        $output->writeln('<info>' . $this->start . ' upload started</info>');

        // for backwards compatibility, remove trailing slash if it exists.
        $tar_dir = rtrim($tar_dir, '/\\');

        if (isset($tar)) {
            $tarfile = $tar;
        } else {
            $tarfile = $app_name . $separator . $build_id . '.tar';
        }
        if ($output->isVerbose()) {
            echo "tar is going to be saved as $tarfile at $tar_dir \n";
        }

        $localFileSha1 = sha1_file("$tar_dir/$tarfile");
        if ($output->isVerbose()) {
            echo "logging to server $server using port $port \n";
        }

        $sftp = new SFTP($server, $port, $timeout);
        $sftp->setTimeout(240);

        $auth = new RSA();
        if (isset($ssh_key_password)) {
            if ($output->isVerbose()) {
                echo "using ssh key with password \n";
            }
            $auth->setPassword($ssh_key_password);
        }
        $auth->loadKey($identity_file_content);
        if ($output->isVerbose()) {
            echo "loaded ssh key $identity_file \n";
        }
        try {
            if ($output->isVerbose()) {
                echo "loggin in to server \n";
            }
            $login = $sftp->login($user, $auth);

            if (!$login) {
                throw new Exception('login Failed using ' . $identity_file . ' and user ' . $user . ' at ' . $server);
            }
            $transfer = $sftp->put("$targetdir/$tarfile", "$tar_dir/$tarfile", 1);
            if (!$transfer) {
                throw new Exception('Upload failed of ' . $tarfile);
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        $tarExists = $sftp->file_exists("$tar_dir/$tarfile");
        // Setting default value

        if ($output->isVerbose()) {
            echo "checksha1 is set to $checksha1 \n";
        }
        $remoteFileSha1 = null;
        if ($tarExists) {
            if ($checksha1 === true) {
                $remoteFileSha1 = $sftp->exec("sha1sum $tar_dir/$tarfile | awk '{print $1}'");
                if ($output->isVerbose()) {
                    echo "tar is at $tar_dir/$tarfile\n";
                    echo "local file hash is $localFileSha1\n";
                    echo "remote file hash is $remoteFileSha1\n";
                    echo "SHA1 for file match\n";
                }
                if (trim($localFileSha1) == trim($remoteFileSha1)) {
                    $output->writeln("<info>$this->mark tar uploaded</info>");
                } else {
                    echo "SHA1 for file do not match.";
                    exit(1);
                }
            } else {
                echo 'upload seems to be successful, but SHA1 for file is not checked' . " $checksha1\n";
            }
        } else {
            if ($output->isVerbose()) {
                echo "tar is at $tar_dir/$tarfile\n";
                echo "local file hash is $localFileSha1\n";
                echo "remote file hash is $remoteFileSha1\n";
            }
            echo 'check for upload did not succeed.' . "\n";
            exit(1);
        }
        $sftp->disconnect();
        $output->writeln("<info>$this->heart upload finished</info>");

        if ($output->isVerbose()) {
            echo 'tar is going to be saved ' . $keeptar . "\n";
            echo 'path to tar ' . "$tar_dir/$tarfile" . "\n";
        }
        if ($keeptar === true) {
            if ($output->isVerbose()) {
                echo "tar file is not deleted \n";
            }
        } else {
            unlink("$tar_dir/$tarfile");
            if ($output->isVerbose()) {
                echo "tar file is deleted \n";
            }
        }

        return 0;
    }
}

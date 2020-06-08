<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Config\Definition\Exception\Exception;

class MoveCommand extends DropcatCommand
{

    protected function configure()
    {
        $HelpText = 'The <info>move</info> connects to remote server and unpacks the site tar and moves it to path.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat move</info>
To override config in dropcat.yml, using options:
<info>dropcat move -server 127.0.0.0 -i my_pub.key</info>';

        $this->setName("move")
            ->setDescription("Moves site in place")
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
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Id',
                        $this->configuration->localEnvironmentBuildId()
                    ),
                    new InputOption(
                        'separator',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Name separator',
                        $this->configuration->localEnvironmentSeparator()
                    ),
                    new InputOption(
                        'tar',
                        't',
                        InputOption::VALUE_OPTIONAL,
                        'Tar name',
                        $this->configuration->localEnvironmentTarName()
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
                        'identity_file',
                        'i',
                        InputOption::VALUE_OPTIONAL,
                        'Identify file',
                        $this->configuration->remoteEnvironmentIdentifyFile()
                    ),
                    new InputOption(
                        'ssh_key_password',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'SSH key password',
                        $this->configuration->localEnvironmentSshKeyPassword()
                    ),
                  /*  new InputOption(
                        'target_path',
                        'tp',
                        InputOption::VALUE_OPTIONAL,
                        'Target path',
                        $this->configuration->remoteEnvironmentTargetPath()
                    ),*/
                    new InputOption(
                        'web_root',
                        'w',
                        InputOption::VALUE_OPTIONAL,
                        'Web root',
                        $this->configuration->remoteEnvironmentWebRoot()
                    ),
                    new InputOption(
                        'temp_folder',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Temp folder',
                        $this->configuration->remoteEnvironmentTempFolder()
                    ),
                    new InputOption(
                        'alias',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Symlink alias to deployed site',
                        $this->configuration->remoteEnvironmentAlias()
                    ),
                new InputOption(
                    'keeptar',
                    null,
                    InputOption::VALUE_NONE,
                    'Keep tar after move (defaults to no)'
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
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        #$target_path = $input->getOption('target_path');
        $port = $input->getOption('ssh_port');
        $ssh_key_password = $input->getOption('ssh_key_password');
        $identity_file = $input->getOption('identity_file');
        $identity_file_content = file_get_contents($identity_file);
        $web_root = $input->getOption('web_root');
        $alias = $input->getOption('alias');
        $temp_folder = $input->getOption('temp_folder');
        $keeptar = $input->getOption('keeptar') ? true : false;

        $output->writeln('<info>' . $this->start . ' move started</info>');

        if (isset($tar)) {
            $tarfile = $tar;
        } else {
            $tarfile = $app_name . $separator . $build_id . '.tar';
        }
        $deploy_folder = "$app_name$separator$build_id";

        if ($output->isVerbose()) {
            echo "deploy folder: $deploy_folder\n";
            echo "tarfile: $tarfile\n";
        }

        $ssh = new SSH2($server, $port);
        $ssh->setTimeout(999);
        $auth = new RSA();
        if (isset($ssh_key_password)) {
            $auth->setPassword($ssh_key_password);
        }
        $auth->loadKey($identity_file_content);

        try {
            $login = $ssh->login($user, $auth);
            if (!$login) {
                throw new Exception('Login Failed using ' . $identity_file . ' and user ' . $user . ' at ' . $server
                . ' ' . $ssh->getLastError());
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        $ssh->exec("mkdir $temp_folder/$deploy_folder");
        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "Could not create temp folder for deploy, error code $status\n";
            exit($status);
        }
        $ssh->exec("mv $temp_folder/$tarfile $temp_folder/$deploy_folder/");
        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "Could not move tar to tar folder, error code $status\n";
            exit($status);
        }
        if ($output->isVerbose()) {
            echo "path to tar to unpack is: " . $temp_folder . '/' . $deploy_folder . '/' . $tarfile . "\n";
        }
        $ssh->exec(
            'tar xvf ' . $temp_folder . '/' . $deploy_folder . '/' . $tarfile .
            ' -C' . $temp_folder . '/' . $deploy_folder
        );
        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "Could not untar tar, error code $status\n";
            exit($status);
        }
        if ($output->isVerbose()) {
            echo 'file ' . $tarfile . " unpacked\n";
        }
        if (!($keeptar)) {
            $ssh->exec('rm ' . $temp_folder . '/' . $deploy_folder . '/' . $tarfile);
            $status = $ssh->getExitStatus();
            if ($status !== 0) {
                echo "Could not remove tar file, error code $status\n";
                exit($status);
            }
            if ($output->isVerbose()) {
                echo 'removed tar file ' . $tarfile . "\n";
            }
        }
        $ssh->exec('mv ' . $temp_folder . '/' . $deploy_folder . ' ' . $web_root . '/' . $deploy_folder);
        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "Folder not in place, error code $status\n";
            exit($status);
        }
        if ($output->isVerbose()) {
            echo "path to deployed folder is: " . $web_root . '/' . $deploy_folder . "\n";
        }

        $cmd = 'ln -sfn ' . $web_root . '/' . $deploy_folder . ' ' . $web_root . '/' . $alias;
        if ($output->isVerbose()) {
            $output->writeln("<info>Symlinking: $cmd</info>");
        }
        $ssh->exec($cmd);
        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            $output->writeln("<info>Could not create symlink from $web_root/$deploy_folder to " .
            "$web_root/$alias, error code $status</info>");
            return $status;
        }

        if ($output->isVerbose()) {
            echo "alias to deployed folder is: " . $web_root . '/' . $alias . "\n";
        }
        $ssh->disconnect();

        $output->writeln("<info>$this->heart move finished</info>");

        return 0;
    }
}

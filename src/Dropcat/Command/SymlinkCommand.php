<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class SymlinkCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>symlink</info> command will import.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat symlink</info>
To override config in dropcat.yml, using options:
<info>dropcat dbimport  -o /var/www/test --symlink=/var/www/foo</info>';

        $this->setName("symlink")
            ->setDescription("Create symlink for target on server, intended for files folders")
            ->setDefinition(
                array(
                    new InputOption(
                        'original_path',
                        'o',
                        InputOption::VALUE_OPTIONAL,
                        'Original path',
                        $this->configuration->siteEnvironmentOriginalPath()
                    ),
                    new InputOption(
                        'symlink',
                        'sl',
                        InputOption::VALUE_OPTIONAL,
                        'Symlink',
                        $this->configuration->siteEnvironmentSymLink()
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
                        'skp',
                        InputOption::VALUE_OPTIONAL,
                        'SSH key password',
                        $this->configuration->localEnvironmentSshKeyPassword()
                    ),
                    new InputOption(
                        'web_root',
                        'w',
                        InputOption::VALUE_OPTIONAL,
                        'Web root',
                        $this->configuration->remoteEnvironmentWebRoot()
                    ),
                    new InputOption(
                        'alias',
                        'aa',
                        InputOption::VALUE_OPTIONAL,
                        'Symlink alias',
                        $this->configuration->remoteEnvironmentAlias()
                    ),
                )
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $original = $input->getOption('original_path');
        $symlink = $input->getOption('symlink');
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $port = $input->getOption('ssh_port');
        $ssh_key_password = $input->getOption('ssh_key_password');
        $web_root = $input->getOption('web_root');
        $alias = $input->getOption('alias');
        $identity_file = $input->getOption('identity_file');
        $identity_file_content = file_get_contents($identity_file);

        $output->writeln('<info>' . $this->start . ' symlink started</info>');


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

        $ssh->exec("rm $symlink.backup");
        $ssh->exec("mv -b $symlink $symlink.backup");
        $ssh->exec("ls -l $original");
        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "original folder does not exist, creating it\n";
            $ssh->exec("mkdir -p $original");
            $status = $ssh->getExitStatus();
            if ($status !== 0) {
                echo "could not create orginal folder, $original, you need to create it manually, error code $status\n";
                exit(1);
            }
        }
        $ssh->exec('ln --backup -snf ' . $original . ' ' . $symlink);
        $output->writeln("<info>$this->heart symlink finished</info>");

        return 0;
    }
}

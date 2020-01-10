<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class DebugCheckConnectionCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command will check connection for env.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the current dir):
<info>dropcat check-connection</info>';

        $this->setName("debug:check-connection")
        ->setDescription("check ssh connection")
        ->setDefinition(
            array(
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
                'identity_file',
                'if',
                InputOption::VALUE_OPTIONAL,
                'Identify file',
                $this->configuration->remoteEnvironmentIdentifyFile()
            ),

            )
        )
        ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $ssh_port = $input->getOption('ssh_port');
        $identity_file = $input->getOption('identity_file');
        $identity_file_content = file_get_contents($identity_file);
        $ssh_key_password = $input->getOption('ssh_key_password');

        $ssh = new SSH2($server, $ssh_port);
        $auth = new RSA();
        if (isset($ssh_key_password)) {
            $auth->setPassword($ssh_key_password);
        }
        $auth->loadKey($identity_file_content);

        try {
            $login = $ssh->login($user, $auth);
            if ($output->isVerbose()) {
                print_r($auth);
                $output->writeln("<info>user: $user pass: $ssh_key_password server: $server</info>");
            }
            if (!$login) {
                throw new Exception('Login Failed using ' . $identity_file . ' and user ' . $user . ' at ' . $server
                . ' ' . $ssh->getLastError());
            }
        } catch (Exception $e) {
            echo $e->getMessage() ."\n";
            $output->writeln('<error>Task: check-connection failed</error>');
            exit(1);
        }

        try {
            // run simple whomami
            $test_user = $ssh->exec("/usr/bin/whoami");
            $test_user = str_replace(array("\r", "\n"), '', trim($test_user));
            $hostname = $ssh->exec("/bin/hostname");
            $hostname = str_replace(array("\r", "\n"), '', trim($hostname));

            if (!$test_user) {
                throw new Exception('Failed running test using ' . $identity_file . ' and user ' . $user . ' at ' . $server
                . ' ' . $ssh->getLastError());
            }
        } catch (Exception $e) {
            echo $e->getMessage() ."\n";
            $output->writeln('<error>Task: check-connection failed</error>');
            $ssh->disconnect();
            exit(1);
        }
        $output->writeln('<info>Successfully logged in to server as user <question>' . $test_user . '</question> on <question>' . $hostname .'</question>.</info>');
        $ssh->disconnect();
    }
}

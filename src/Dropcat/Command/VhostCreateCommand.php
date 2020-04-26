<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VhostCreateCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>vhost:create</info> command will create a vhost for a site.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat vhost:create</info>
To override config in dropcat.yml, using options:
<info>dropcat vhost:create --target=/etc/httpd/conf.d</info>';
            $this->setName("vhost:create")
            ->setDescription("create a vhost on a remote server")
            ->setDefinition(
                array(
                new InputOption(
                    'target',
                    't',
                    InputOption::VALUE_OPTIONAL,
                    'Vhost target folder',
                    $this->configuration->vhostTarget()
                ),
                    new InputOption(
                        'file-name',
                        'f',
                        InputOption::VALUE_OPTIONAL,
                        'Vhost file name',
                        $this->configuration->vhostFileName()
                    ),
                    new InputOption(
                        'port',
                        'vp',
                        InputOption::VALUE_OPTIONAL,
                        'Port',
                        $this->configuration->vhostPort()
                    ),
                    new InputOption(
                        'document-root',
                        'dr',
                        InputOption::VALUE_OPTIONAL,
                        'Document root',
                        $this->configuration->vhostDocumentRoot()
                    ),
                    new InputOption(
                        'server-name',
                        'sn',
                        InputOption::VALUE_OPTIONAL,
                        'Document root',
                        $this->configuration->vhostServerName()
                    ),
                    new InputOption(
                        'server-alias',
                        'sa',
                        InputOption::VALUE_OPTIONAL,
                        'Server alias',
                        $this->configuration->vhostServerAlias()
                    ),
                    new InputOption(
                        'extra',
                        've',
                        InputOption::VALUE_OPTIONAL,
                        'Server extra',
                        $this->configuration->vhostExtra()
                    ),
                    new InputOption(
                        'bash-command',
                        'bc',
                        InputOption::VALUE_OPTIONAL,
                        'Bash command',
                        $this->configuration->vhostBashCommand()
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
                        'ssh-port',
                        'p',
                        InputOption::VALUE_OPTIONAL,
                        'SSH port',
                        $this->configuration->remoteEnvironmentSshPort()
                    ),
                    new InputOption(
                        'identity-file',
                        'i',
                        InputOption::VALUE_OPTIONAL,
                        'Identify file',
                        $this->configuration->remoteEnvironmentIdentifyFile()
                    ),
                    new InputOption(
                        'ssh-key-password',
                        'skp',
                        InputOption::VALUE_OPTIONAL,
                        'SSH key password',
                        $this->configuration->localEnvironmentSshKeyPassword()
                    ),
                )
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getOption('target');
        $file_name = $input->getOption('file-name');
        $document_root = $input->getOption('document-root');
        $vhost_port = $input->getOption('port');
        $server_name = $input->getOption('server-name');
        $server_alias = $input->getOption('server-alias');
        $extra = $input->getOption('extra');
        $bash_command = $input->getOption('bash-command');
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $port = $input->getOption('ssh-port');
        $ssh_key_password = $input->getOption('ssh-key-password');
        $identity_file = $input->getOption('identity-file');
        $identity_file_content = file_get_contents($identity_file);

        if (!isset($server_alias)) {
            $server_alias = '';
        }

        $runbash = '';
        if (isset($bash_command)) {
            $runbash = " && $bash_command";
        }

        $virtualHost ="<VirtualHost *:$vhost_port>\n" .
          "  DocumentRoot $document_root\n" .
          "  ServerName $server_name\n\n" .
          "$server_alias\n" .
          "$extra\n" .
          "</VirtualHost>\n";
        $aliasCreate= Process::fromShellCommandline(
            "ssh -o LogLevel=Error $user@$server -p $port \"echo '$virtualHost' > $target/$file_name $runbash\""
        );
        $aliasCreate->setTimeout(999);
        $aliasCreate->run();
        // executes after the command finishes
        if (!$aliasCreate->isSuccessful()) {
            throw new ProcessFailedException($aliasCreate);
        }

        echo $aliasCreate->getOutput();

        $output->writeln('<info>Task: vhost:create finished</info>');

        return 0;
    }
}

<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CleanDeploysCommand extends DropcatCommand
{

    protected static $defaultName = 'clean:deploys';

    protected function configure()
    {
        $HelpText = '<info>Remove old deploy directories</info>';

        $this->setDescription("Remove old deploy directories.")
            ->setAliases(['clean:deploy'])
            ->addOption(
                'keep',
                'k',
                InputOption::VALUE_REQUIRED,
                'Number of dirs to keep. Default: 5.',
                5
            )
            ->addOption(
                'web-root',
                'w',
                InputOption::VALUE_REQUIRED,
                'Web root, where the deploy dirs are.',
                $this->configuration->remoteEnvironmentWebRoot())
            ->addOption(
                'prefix',
                'f',
                InputOption::VALUE_REQUIRED,
                'Prefix to search for the deploy dirs. Default is the app name.',
                $this->configuration->localEnvironmentAppName()
            )
            ->addOption(
                'alias',
                'a',
                InputOption::VALUE_REQUIRED,
                'Name of the symlink to the main deploy dir. Default: remote environment alias.',
                $this->configuration->remoteEnvironmentAlias()
            )
            ->addOption(
                'ssh-key',
                'i',
                InputOption::VALUE_REQUIRED,
                'SSH key',
                $this->configuration->remoteEnvironmentIdentifyFile()
            )
            ->addOption(
                'ssh-port',
                'p',
                InputOption::VALUE_REQUIRED,
                'SSH port',
                $this->configuration->remoteEnvironmentSshPort()
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User (ssh)',
                $this->configuration->remoteEnvironmentSshUser()
            )
            ->addOption(
                'server',
                's',
                InputOption::VALUE_REQUIRED,
                'Server',
                $this->configuration->remoteEnvironmentServerName()
            )
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_REQUIRED,
                'Timeout',
                $this->configuration->timeOut()
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>' . $this->start . ' ' . $this->getName() . ' command started</info>');

        $sshServer = $input->getOption('server');
        $sshPort = $input->getOption('ssh-port');
        $timeout = $input->getOption('timeout');
        $identityFile = $input->getOption('ssh-key');
        $sshUser = $input->getOption('user');

        if ($output->isVeryVerbose()) {
            define('NET_SSH2_LOGGING', SSH2::LOG_COMPLEX);
        }

        // ssh and do stuff
        $ssh = new SSH2($sshServer, $sshPort, $timeout);
        $key = new RSA();
        $contents = file_get_contents($identityFile);
        $key->loadKey($contents);
        if (!$ssh->login($sshUser, $key)) {
            $err = $ssh->getErrors();
            $output->writeln("<error>Error: could not ssh to $sshUser@$sshServer on port $sshPort with key $identityFile.</error>");
            if (is_array($err)) {
                foreach ($err as $error) {
                    $output->writeln("<error>SSH error: $error</error>");
                }
            }
            if ($output->isVeryVerbose()) {
                $output->writeln($ssh->getLog());
            }
            throw new \Exception($this->getName() . ' command failed.');
        }

        // All credit to miiimooo
        $script = <<<EOF
#!/usr/bin/env bash
NUM={$input->getOption('keep')}
FOLDER={$input->getOption('web-root')}
PREFIX={$input->getOption('prefix')}
SYMLINK={$input->getOption('alias')}
counter=0
removed=0
cd "\$FOLDER"
READLINK=$(basename "$(readlink "\$SYMLINK")")
for entry in $(ls -t | egrep "^\$PREFIX") ; do
  echo "ENTRY \$entry \$READLINK"
  [ "\$entry" == "\$READLINK" ] && echo "skip symlinked entry \$entry" && continue
  [ "\$entry" == "\$SYMLINK" ] &&  echo "skip symlink entry \$entry (unlikely)" && continue
  [ ! -d "\$entry" ] && echo "skip NOT directory \$entry" && continue
  counter=\$((counter+1))
  if [[ "\$counter" -ge "\$NUM" ]]; then
    echo "removing \$entry (\$counter)"
    rm -rf "\$entry"
    removed=\$((removed+1))
  fi
done

echo "All done. Of a total of \$counter old deploys \$removed were removed."

EOF;
        if ($output->isVeryVerbose()) {
            $output->writeln("<comment>Executing script:</comment>");
            $output->writeln("<comment>$script</comment>");
        }

        $result = $ssh->exec($script);

        $output->writeln("<comment>$result</comment>");

        if ($output->isVeryVerbose()) {
            $output->writeln('<comment>' . $ssh->getLog() . '</comment>');
        }

        if ($ssh->getExitStatus() !== 0) {
            $output->writeln('<error>Something went wrong.</error>');
            $output->writeln('<error>' . $this->mark . ' ' . $this->getName() . ' command failed</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>' . $this->mark . ' ' . $this->getName() . ' command finished</info>');

        return Command::SUCCESS;
    }
}

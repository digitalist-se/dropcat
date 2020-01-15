<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Dropcat\Lib\Write;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class GenerateDrushAliasCommand extends DropcatCommand {

    protected function configure() {
        $HelpText = 'The <info>%command.name%</info> command will create drush alias.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the current dir):
<info>dropcat generate:drush-alias</info>
To override config in dropcat.yml, using options, creates alias to stage env.
<info>dropcat generate:drush-alias --env=stage</info>';

        $this->setName("generate:drush-alias")
          ->setDescription("creates a local drush alias")
          ->setHelp($HelpText)
          ->setDefinition(
            [
              new InputOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                "Create drush alias for local use (this option is normally not needed)."
              ),
              new InputOption(
                'location',
                'o',
                InputOption::VALUE_OPTIONAL,
                "Where to save the drush alias file.",
                NULL
              ),
            ]
          );
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        $env = $input->getParameterOption([
          '--env',
          '-e',
        ], getenv('DROPCAT_ENV') ?: 'dev');
        $location = $input->getOption('location');

        if ($this->configuration) {
            $drushAliasName = $this->configuration->siteEnvironmentDrushAlias();
            $siteName = $this->configuration->siteEnvironmentName();
            $webroot = $this->configuration->remoteEnvironmentWebRoot();
            $alias = $this->configuration->remoteEnvironmentAlias();
            $url = $this->configuration->siteEnvironmentUrl();
            $sshport = $this->configuration->remoteEnvironmentSshPort();
            $server = $this->configuration->remoteEnvironmentServerName();
            $user = $this->configuration->remoteEnvironmentSshUser();
            $drushMemoryLimit = $this->configuration->remoteEnvironmentDrushMemoryLimit();
            $local = $input->getOption('local') ? TRUE : FALSE;
            if ($local === TRUE) {
                $sshport = $this->configuration->remoteEnvironmentLocalSshPort() ?
                  $this->configuration->remoteEnvironmentLocalSshPort() :
                  $this->configuration->remoteEnvironmentSshPort();
                $server = $this->configuration->remoteEnvironmentLocalServerName() ?
                  $this->configuration->remoteEnvironmentLocalServerName() :
                  $this->configuration->remoteEnvironmentServerName();
                $user = $this->configuration->remoteEnvironmentLocalSshUser() ?
                  $this->configuration->remoteEnvironmentLocalSshUser() :
                  $this->configuration->remoteEnvironmentSshUser();
            }
            if ($output->isVerbose()) {
                echo "ssh user is $user\n";
                echo "server is $server\n";
                echo "port is $sshport\n";
                echo "drush memory limit is $drushMemoryLimit\n";
            }

            $conf = [
              'env' => $env,
              'drush-alias-name' => $drushAliasName,
              'site-name' => $siteName,
              'server' => $server,
              'user' => $user,
              'web-root' => $webroot,
              'alias' => $alias,
              'url' => $url,
              'ssh-port' => $sshport,
              'drush-memory-limit' => $drushMemoryLimit,
              'location' => $location,
            ];

            $write = new Write();
            $success = $write->drushAlias($conf, $output->isVerbose());

            if ($success === 0) {
                $output->writeln('<info>Task: generate:drush-alias finished.</info>');
            } else {
                $output->writeln('<error>Task: generate:drush-alias failed.</error>');
                return 1;
            }

        }
        else {
            $output->writeln('<error>I cannot create any alias, please check your --env parameter</error>');
            return 1;
        }

        return 0;
    }
}

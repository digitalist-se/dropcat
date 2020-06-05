<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Dropcat\Lib\CreateDrushAlias;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class GenerateDrushAliasCommand extends DropcatCommand
{
    protected function configure()
    {
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
            array(
            new InputOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                "Create drush alias for local use (this option is normaly not needed)."
            ),
            )
        );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->configuration) {
            $siteName = $this->configuration->siteEnvironmentName();
            $webroot = $this->configuration->remoteEnvironmentWebRoot();
            $alias = $this->configuration->remoteEnvironmentAlias();
            $url = $this->configuration->siteEnvironmentUrl();
            $sshport = $this->configuration->remoteEnvironmentSshPort();
            $server = $this->configuration->remoteEnvironmentServerName();
            $user = $this->configuration->remoteEnvironmentSshUser();
            $drushMemoryLimit = $this->configuration->remoteEnvironmentDrushMemoryLimit();
            $local = $input->getOption('local') ? true : false;
            $identityOption = $this->configuration->localEnvironmentSshIdentityFile();

            if ($local === true) {
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
                echo "local identity option is $identityOption\n";
            }

            $drushAlias = new CreateDrushAlias();
            $drushAlias->setName($siteName);
            $drushAlias->setServer($server);
            $drushAlias->setUser($user);
            $drushAlias->setWebRoot($webroot);
            $drushAlias->setSitePath($alias);
            $drushAlias->setUrl($url);
            $drushAlias->setSSHPort($sshport);
            $drushAlias->setDrushMemoryLimit($drushMemoryLimit);
            $drushAlias->setSshIdentityOption($identityOption);

            $home = new CreateDrushAlias();
            $home_dir = $home->drushServerHome();

            $drush_alias_name = $this->configuration->siteEnvironmentDrushAlias();

            $drush_file = new Filesystem();

            try {
                $drush_file->dumpFile($home_dir . '/.drush/' . $drush_alias_name .
                '.aliases.drushrc.php', $drushAlias->getValue());
            } catch (IOExceptionInterface $e) {
                echo 'An error occurred while creating your file at ' . $e->getPath();
            }

            $output->writeln('<info>Task: generate:drush-alias finished. You could now use:</info>');
            $output->writeln('<info>drush @' . $siteName . '</info>');
        } else {
            echo 'I cannot create any alias, please check your --env parameter';
        }
    }
}

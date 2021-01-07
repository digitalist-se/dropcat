<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SiteInstallCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command installs a drupal site,.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat site-install</info>
To override config in dropcat.yml, using options:
<info>dropcat site-install -d mysite</info>';

        $this->setName("site:install")
            ->setAliases(["site-install"])
          ->setDescription("Site install")
        ->setDefinition(
            array(
                new InputOption(
                    'drush_alias',
                    'd',
                    InputOption::VALUE_OPTIONAL,
                    'Drush alias',
                    $this->configuration->getFullDrushAlias()
                ),
                new InputOption(
                    'profile',
                    'p',
                    InputOption::VALUE_OPTIONAL,
                    'Profile',
                    $this->configuration->siteEnvironmentProfile()
                ),
                new InputOption(
                    'time_out',
                    'to',
                    InputOption::VALUE_OPTIONAL,
                    'Time out',
                    $this->configuration->timeOut()
                ),
                new InputOption(
                    'admin_pass',
                    'ap',
                    InputOption::VALUE_OPTIONAL,
                    'Admin pass',
                    $this->configuration->siteEnvironmentAdminPass()
                ),
                new InputOption(
                    'admin_user',
                    'au',
                    InputOption::VALUE_OPTIONAL,
                    'Admin user',
                    $this->configuration->siteEnvironmentAdminUser()
                ),
                new InputOption(
                    'install_options',
                    'io',
                    InputOption::VALUE_OPTIONAL,
                    'Drush install options',
                    $this->configuration->siteEnvironmentDrushInstallOptions()
                ),

              )
        )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drush_alias      = $input->getOption('drush_alias');
        $profile          = $input->getOption('profile');
        $timeout          = $input->getOption('time_out');
        $admin_pass       = $input->getOption('admin_pass');
        $admin_user       = $input->getOption('admin_user');
        $install_options  = $input->getOption('install_options');

        $output->writeln('<info>' . $this->start . ' site install started</info>');

        $cmd = [
              'drush',
              "@$drush_alias",
              'si',
              "$profile",
              "--account-name=$admin_user",
              "--account-pass=$admin_pass",
              '-y',
              "$install_options",
              '-v'
        ];
        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->mustRun();
        if ($output->isVerbose()) {
            $output->writeln('<comment>' . $process->getOutput() . '</comment>');
        }

        $output->writeln('<info>' . $this->heart . ' site install finished</info>');

        return 0;
    }
}

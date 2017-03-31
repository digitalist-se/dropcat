<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Dropcat\Services\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SiteInstallCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>site-install</info> command installs a drupal site,.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat site-install</info>
To override config in dropcat.yml, using options:
<info>dropcat site-install -d mysite -c myconfig</info>';

        $this->setName("site-install")
          ->setDescription("Site install")
          ->setDefinition(
              array(
                  new InputOption(
                      'drush_alias',
                      'd',
                      InputOption::VALUE_OPTIONAL,
                      'Drush alias',
                      $this->configuration->siteEnvironmentDrushAlias()
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

        $process = $this->runProcess(
            "drush @$drush_alias si $profile --account-name=$admin_user --account-pass=$admin_pass -y $install_options"
        );
        $process->setTimeout($timeout);
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        $output->writeln('<info>Task: configimport finished</info>');

    }
}

<?php

namespace Dropcat\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunYarnInstallCommand extends RunCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>node:npm-install</info> command will run npm install.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat node:npm-install</info>
To override config in dropcat.yml, using options:
<info>dropcat run-local --nvmrc=/foo/bar/.nvmrc</info>';

        $this->setName("node:yarn-install")
            ->setDescription("do a yarn install")
            ->setDefinition(
                array(
                    new InputOption(
                        'nvm-dir',
                        'nd',
                        InputOption::VALUE_REQUIRED,
                        'NVM directory',
                        $this->configuration->nodeNvmDirectory()
                    ),
                    new InputOption(
                        'nvmrc',
                        'nc',
                        InputOption::VALUE_OPTIONAL,
                        'Path to .nvmrc file',
                        $this->configuration->nodeNvmRcFile()
                    ),
                )
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>' . $this->start . ' node:yarn-install started</info>');

        $nvmDir = $input->getOption('nvm-dir');
        $output->writeln('<info>NVM Directory: ' . var_export($this->configuration->nodeNvmDirectory(), true) . '</info>',
            OutputInterface::VERBOSITY_DEBUG);

        if (!isset($nvmDir) || empty($nvmDir)) {
            $output->writeln("<error>$this->error Path to NVM could not be found.</error>");
            return 1;
        }
        $nodeNvmRcFile = $input->getOption('nvmrc');
        if ($nodeNvmRcFile === null) {
            $nodeNvmRcFile = getcwd() . '/.nvmrc';
        }
        if (!file_exists($nodeNvmRcFile)) {
            $output->writeln("<error>$this->error No .nvmrc file found.</error>");
            return 1;
        }
        $yarnInstall = Process::fromShellCommandline("bash -cl 'yarn install'");
        $yarnInstall->setTimeout(600);
        $yarnInstall->mustRun();
        $output->writeln('<comment>' . $yarnInstall->getOutput() . '</comment>', OutputInterface::VERBOSITY_VERBOSE);

        $output->writeln('<info>' . $this->heart . ' node:yarn-install finished</info>');

        return 0;
    }
}

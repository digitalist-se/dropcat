<?php

namespace Dropcat\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunNpmInstallCommand extends RunCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>node:npm-install</info> command will run npm install.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat node:npm-install</info>
To override config in dropcat.yml, using options:
<info>dropcat run-local --nvmrc=/foo/bar/.nvmrc</info>';

        $this->setName("node:npm-install")
            ->setDescription("do a npm install")
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
        $output->writeln('<info>' . $this->start . ' node:npm-install started</info>');

        $nvmDir = $input->getOption('nvm-dir');
        if (!isset($nvmDir)) {
            throw new Exception('<error>No nvm dir found in options.</error>');
        }
        $nodeNvmRcFile = $input->getOption('nvmrc');
        if ($nodeNvmRcFile === null) {
            $nodeNvmRcFile = getcwd() . '/.nvmrc';
        }
        if (!file_exists($nodeNvmRcFile)) {
            throw new Exception('<error>No .nvmrc file found.</error>');
        }
        $npmInstall = Process::fromShellCommandline("bash -c 'source $nvmDir/nvm.sh' && . $nvmDir/nvm.sh && nvm install && npm install");
        $npmInstall->setTimeout(3600);
        $npmInstall->mustRun();
        if ($output->isVerbose()) {
            $output->writeln('<comment>' . $npmInstall->getOutput() . '</comment>');
        }

        $output->writeln('<info>' . $this->heart . ' node:npm-install finished</info>');

        return 0;
    }
}

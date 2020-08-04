<?php

namespace Dropcat\Command;

use Dropcat\Lib\NvmCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RunNpmInstallCommand extends NvmCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>node:npm-install</info> command will run npm install.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the current dir):
<info>dropcat node:npm-install</info>
To override config in dropcat.yml, using options:
<info>dropcat run-local --nvmrc=/foo/bar/.nvmrc</info>';

        $this->setName("node:npm-install")
            ->setDescription("do a npm install")
            ->setDefinition(
                [
                    new InputOption(
                        'nvmrc',
                        'nc',
                        InputOption::VALUE_OPTIONAL,
                        'Path to .nvmrc file',
                        $this->configuration->nodeNvmRcFile()
                    ),
                ]
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>' . $this->start . ' node:npm-install started</info>');

        $this->nvmService->install($input->getOption('nvmrc'));

        $npmInstall = Process::fromShellCommandline("bash -cl 'npm install'");
        $npmInstall->setTimeout(600);
        $npmInstall->mustRun();
        $output->writeln('<comment>' . $npmInstall->getOutput() . '</comment>', OutputInterface::VERBOSITY_VERBOSE);

        $output->writeln('<info>' . $this->heart . ' node:npm-install finished</info>');

        return self::SUCCESS;
    }
}

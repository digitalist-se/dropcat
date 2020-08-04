<?php

namespace Dropcat\Command;

use Dropcat\Lib\NvmCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RunYarnInstallCommand extends NvmCommand
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
        $output->writeln('<info>' . $this->start . ' node:yarn-install started</info>');

        $this->nvmService->install($input->getOption('nvmrc'));

        $yarnInstall = Process::fromShellCommandline("bash -cl 'yarn install'");
        $yarnInstall->setTimeout(600);
        $yarnInstall->mustRun();
        $output->writeln('<comment>' . $yarnInstall->getOutput() . '</comment>', OutputInterface::VERBOSITY_VERBOSE);

        $output->writeln('<info>' . $this->heart . ' node:yarn-install finished</info>');

        return self::SUCCESS;
    }
}

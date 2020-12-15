<?php

namespace Dropcat\Command;

use Dropcat\Lib\NCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RunNpmInstallCommand extends NCommand
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
                        InputOption::VALUE_REQUIRED,
                        'Path to .nvmrc file',
                        $this->configuration->nodeNvmRcFile()
                    )
                ]
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>' . $this->start . ' node:npm-install started</info>');

        if (!$this->nService->useAndRunCommand('npm install')) {
            $output->writeln('<info>' . $this->error . ' node:npm-install failed</info>');
            throw new \Exception("Theme building failed.");
        }

        $output->writeln('<info>' . $this->heart . ' node:npm-install finished</info>');

        return self::SUCCESS;
    }
}

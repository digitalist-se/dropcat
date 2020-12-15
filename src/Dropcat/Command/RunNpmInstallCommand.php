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
                        InputOption::VALUE_REQUIRED,
                        'Path to .nvmrc file',
                        $this->configuration->nodeNvmRcFile()
                    ),
                    new InputOption(
                        'nvm-path',
                        'np',
                        InputOption::VALUE_REQUIRED,
                        'Path to nvm directory',
                        $this->configuration->nodeNvmDirectory()
                    ),
                ]
            )
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>' . $this->start . ' node:npm-install started</info>');

        $nvmDir = $input->getOption('nvm-path');
        $this->nvmService->setNvmDir($nvmDir);
        $success = $this->nvmService->install($input->getOption('nvmrc'));
        if (!$success) {
            throw new \Exception("NVM install failed.");
        }

        $npmInstall = Process::fromShellCommandline(". $nvmDir/nvm.sh && npm install");
        $npmInstall->setTimeout(600);
        $success = $npmInstall->run();
        echo 'NPM install exit code: ' . $npmInstall->getExitCode();
        $errOut = $npmInstall->getErrorOutput();
        if (!empty($errOut)) {
            $output->writeln('<error>NPM install ErrorOut: ' . $errOut . '</error>');
        }
        $output->writeln('<comment>NPM install StdOut: ' . $npmInstall->getOutput() . '</comment>', OutputInterface::VERBOSITY_VERBOSE);
        if (!$success) {
            throw new \Exception("NPM install failed.");
        }
        $output->writeln('<info>' . $this->heart . ' node:npm-install finished</info>');

        return self::SUCCESS;
    }
}

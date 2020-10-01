<?php


namespace Dropcat\Services;


use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class NvmWrapperService
{
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Install a specific node version with nvm.
     * @param string $nvmRcPath
     * @return bool
     */
    public function install(string $nvmRcPath = '') : bool {
        if (!$this->isNvmRcFilePresent($nvmRcPath)) {
            $msg = 'Error: No nvmrc file found. Specify the node.nvmrc option if the .nvmrc file is not in the project\'s root, or add the file if it is missing.';
            $this->output->writeln("<error>$msg</error>");
            return false;
        }
        if (!$this->isNvmInstalled()) {
            $this->output->writeln("<error>Error: Cannot proceed to nvm install.</error>");
            return false;
        }
        $nvmInstall = Process::fromShellCommandline("bash -cl 'nvm install'");
        $nvmInstall->setTimeout(120);
        try {
            $nvmInstall->mustRun();
            $this->output->writeln("<comment>" . $nvmInstall->getOutput() . "</comment>");
        } catch (ProcessFailedException $e) {
            $this->output->writeln('<error>Error: ' . $nvmInstall->getErrorOutput() . '</error>');
            return false;
        }

        return true;
    }

    /**
     * Use nvm version from .nvmrc file and execute a command with that version.
     * To install a nvm version, call NvmWrapperService->install() first.
     * @param string $command
     * @return bool
     */
    public function useAndRunCommand(string $command) : bool
    {
        $process = Process::fromShellCommandline("bash -cl 'nvm use && $command'");
        $process->setTimeout(360);
        try {
            $process->mustRun();
            $this->output->writeln("<comment>" . $process->getOutput() . "</comment>");
        } catch (ProcessFailedException $e) {
            $this->output->writeln("<error>Error: 'nvm use' & the command '$command' failed.</error>");
            $this->output->writeln("<error>Error: " . $process->getErrorOutput() . "</error>");
            return false;
        }

        return true;
    }

    /**
     * Checks if nvm is loaded in the shell.
     * @return bool
     */
    protected function isNvmInstalled() : bool
    {
        # Check if nvm is loaded in the shell.
        # Notice the -l login option, that will load the nvm.sh script if it is present.
        # It is important that you have added the following to a file that is loaded on bash login (.bashrc for ex.):
        #     export NVM_DIR="$HOME/.nvm"
        #     [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" # This loads nvm
        $checkNvm = Process::fromShellCommandline("bash -cl 'env | grep NVM'");
        try {
            $checkNvm->mustRun();
        } catch (ProcessFailedException $e) {
            $this->output->writeln("<error>NVM was not loaded in the environment.</error>");
            return false;
        }

        return true;
    }

    /**
     * Checks if .nvmrc file exists.
     * @param string $path
     * @return bool
     */
    protected function isNvmRcFilePresent(string $path = '') : bool
    {
        if (empty($path)) {
            $path = getcwd() . '/.nvmrc';
        }
        $this->output->writeln("<comment>Using .nvmrc at $path</comment>", OutputInterface::VERBOSITY_VERBOSE);
        if (!file_exists($path)) {
            return false;
        }

        return true;
    }
}
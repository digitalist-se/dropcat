<?php


namespace Dropcat\Services;


use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class NvmWrapperService
{
    protected OutputInterface $output;

    protected string $nvmDir;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getNvmDir(): string
    {
        return $this->nvmDir;
    }

    /**
     * @param string $nvmDir
     */
    public function setNvmDir(string $nvmDir): void
    {
        $this->nvmDir = $nvmDir;
    }

    /**
     * Install a specific node version with nvm.
     * @param string $nvmRcPath
     * @return bool
     */
    public function install(string $nvmRcPath = '') : bool {
        $nvmDir = $this->getNvmDir();
        if (!isset($nvmDir)) {
            $this->output->writeln("<error>Error: nvm directory was not set.</error>");
            return false;
        }
        if (!$this->isNvmRcFilePresent($nvmRcPath)) {
            $msg = 'Error: No nvmrc file found. Specify the node.nvmrc option if the .nvmrc file is not in the project\'s root, or add the file if it is missing.';
            $this->output->writeln("<error>$msg</error>");
            return false;
        }
        if (!$this->isNvmInstalled()) {
            $this->output->writeln("<error>Error: Cannot proceed to nvm install.</error>");
            return false;
        }
        $nvmInstall = Process::fromShellCommandline(". $nvmDir/nvm.sh && nvm install");
        $nvmInstall->setTimeout(120);
        $nvmInstall->run();
        $exitCode = $nvmInstall->getExitCode();
        $this->output->writeln("<comment>NVM install Exit code: $exitCode</comment>", OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln("<comment>NVM install StdOut: " . $nvmInstall->getOutput() . "</comment>", OutputInterface::VERBOSITY_VERBOSE);
        $this->output->writeln('<error>NVM install ErrorOut: ' . $nvmInstall->getErrorOutput() . '</error>', OutputInterface::VERBOSITY_VERBOSE);

        return $exitCode === 0;
    }

    /**
     * Use nvm version from .nvmrc file and execute a command with that version.
     * To install a nvm version, call NvmWrapperService->install() first.
     * @param string $command
     * @return bool
     */
    public function useAndRunCommand(string $command) : bool
    {
        $nvmDir = $this->getNvmDir();
        if (!isset($nvmDir)) {
            $this->output->writeln("<error>Error: nvm directory was not set.</error>");
            return false;
        }
        $process = Process::fromShellCommandline("bash -c '. $nvmDir/nvm.sh && nvm use && $command'");
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
        $nvmDir = $this->getNvmDir();
        $this->output->writeln("<error>$nvmDir</error>");
        if (!isset($nvmDir)) {
            $this->output->writeln("<error>Error: nvm directory was not set.</error>");
            return false;
        }
        $checkNvm = Process::fromShellCommandline(". $nvmDir/nvm.sh && nvm --version");
        $rawCmd = $checkNvm->getCommandLine();
        $this->output->writeln('<error>' . $rawCmd . '</error>');
        $checkNvm->run();
        $errOut = $checkNvm->getErrorOutput();
        $stdOut = $checkNvm->getOutput();
        $exitCode = $checkNvm->getExitCode();
        $this->output->writeln("<comment>NVM install check – Exit code: $exitCode</comment>", OutputInterface::VERBOSITY_VERBOSE);
        if (!empty($errOut)) {
            $this->output->writeln('<error>NVM install version check – Error: ' . $errOut . '</error>');
        }
        if (!empty($stdOut)) {
            $this->output->writeln('<error>NVM install check – StdOut: ' . $stdOut . '</error>');
        }

        return $exitCode === 0;
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
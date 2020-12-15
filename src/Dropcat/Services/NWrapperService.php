<?php


namespace Dropcat\Services;


use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/*
 * This class depends on:
 * https://github.com/tj/n
 * for node version management.
 */
class NWrapperService
{
    protected OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Use node version from one of the following files (in that order)  and execute a command with that version:
     * - .n-node-version
     * - .node-version
     * - .nvmrc
     * - package.json ('engines' field)
     * @param string $command
     * @return bool
     */
    public function useAndRunCommand(string $command) : bool
    {

        if ($this->isNInstalled()) {
            $process = Process::fromShellCommandline("n auto && $command");
            $process->setTimeout(360);
            try {
                $process->mustRun();
                $this->output->writeln("<comment>n output:</comment>", OutputInterface::VERBOSITY_VERBOSE);
                $this->output->writeln("<comment>" . $process->getOutput() . "</comment>", OutputInterface::VERBOSITY_VERBOSE);
                return true;
            } catch (ProcessFailedException $e) {
                $this->output->writeln("<error>Error: 'n auto (like nvm use)' & the command '$command' failed.</error>");
                $this->output->writeln("<error>Error: " . $process->getErrorOutput() . "</error>");
            }
        }

        return false;
    }

    /**
     * Checks if n is installed.
     * @return bool
     */
    protected function isNInstalled() : bool
    {
        $checkNvm = Process::fromShellCommandline("n --version");
        $checkNvm->run();
        $errOut = $checkNvm->getErrorOutput();
        $stdOut = $checkNvm->getOutput();
        $exitCode = $checkNvm->getExitCode();
        $this->output->writeln("<comment>n install check – Exit code: $exitCode</comment>", OutputInterface::VERBOSITY_VERY_VERBOSE);
        if (!empty($errOut)) {
            $this->output->writeln('<error>n install version check – Error: ' . $errOut . '</error>');
        }
        if (!empty($stdOut)) {
            $this->output->writeln('<comment>n install check – n version:' . $stdOut . '</comment>', OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        return $exitCode === 0;
    }
}
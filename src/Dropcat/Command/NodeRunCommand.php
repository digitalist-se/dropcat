<?php

namespace Dropcat\Command;

use Dropcat\Lib\NCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NodeRunCommand extends NCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>node:run</info> command will run an arbitrary command that you could run with node.
It uses the .nvmrc file of your project or another file that n reads.
<comment>Samples:</comment>
You can run npm scripts, npx, yarn, gulp etc.
To run with default options (using config from dropcat.yml in the current dir):
<info>dropcat node:run "npx cowsay hello"</info>';

        $this->setName("node:run")
            ->setDescription("run a node command")
            ->addArgument('cmd', InputArgument::REQUIRED, 'The command to run, for example "npx cowsay hello"')
            ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('cmd');
        $output->writeln('<info>' . $this->start . ' node:run "' . $command . '" started</info>');

        if (!$this->nService->useAndRunCommand($command)) {
            $output->writeln('<info>' . $this->error . ' node:run failed</info>');
            throw new \Exception("Theme building failed.");
        }

        $output->writeln('<info>' . $this->heart . ' node:run "' . $command . '" finished</info>');

        return self::SUCCESS;
    }
}

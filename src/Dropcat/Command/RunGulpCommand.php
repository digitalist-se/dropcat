<?php

namespace Dropcat\Command;

use Dropcat\Lib\NvmCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class RunGulpCommand extends NvmCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>node:gulp</info> command will run gulp.
    <comment>Samples:</comment>
    To run with default options (using config from dropcat.yml in the currrent dir):
    <info>dropcat node:gulp</info>
    To override config in dropcat.yml, using options:
    <info>dropcat node:gulp --gulp-dir=/foo/bar --nvmrc=/foo/bar/.nvmrc</info>';

        $this->setName("node:gulp")
          ->setDescription("run gulp")
        ->setDefinition(
            array(
                new InputOption(
                    'gulp-dir',
                    'gd',
                    InputOption::VALUE_REQUIRED,
                    'Directory with gulpfile',
                    $this->configuration->gulpDirectory()
                ),
                new InputOption(
                    'gulp-options',
                    'go',
                    InputOption::VALUE_OPTIONAL,
                    'Gulp options',
                    $this->configuration->gulpOptions()
                ),
                new InputOption(
                    'node-env',
                    'ne',
                    InputOption::VALUE_OPTIONAL,
                    'Node environment',
                    $this->configuration->nodeEnvironment()
                ),
              )
        )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gulpDir = $input->getOption('gulp-dir');
        $gulpOptions = $input->getOption('gulp-options');
        $nodeEnv = $input->getOption('node-env');

        $output->writeln('<info>' . $this->start . ' node:gulp started</info>');

        if ($gulpDir === null) {
            $gulpDir = '.';
        }

        $env = null;
        if (isset($nodeEnv)) {
            $env = 'NODE_ENV=' . $nodeEnv;
        }

        if (!$this->nvmService->useAndRunCommand("$env node_modules/gulp/bin/gulp.js --cwd $gulpDir $gulpOptions")) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

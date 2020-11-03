<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AboutCommand extends DropcatCommand
{

    protected static $defaultName = 'about';

    protected static $logo = '
      _|                                                      _|
  _|_|_|  _|  _|_|    _|_|    _|_|_|      _|_|_|    _|_|_|  _|_|_|_|
_|    _|  _|_|      _|    _|  _|    _|  _|        _|    _|    _|
_|    _|  _|        _|    _|  _|    _|  _|        _|    _|    _|
  _|_|_|  _|          _|_|    _|_|_|      _|_|_|    _|_|_|      _|_|
                              _|
                              _|';

    protected function configure()
    {
        $HelpText = '<info>Display the about</info>';

        $this->setName("about")
          ->setDescription("about dropcat")
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>' . static::$logo . '</comment>');
        $output->writeln("<info>dropcat is an open source website delivery tool. " .
          "\ndeveloped by digitalist group in sweden. meow! $this->cat" .
          "</info>");
        return 0;
    }
}

<?php

namespace Dropcat\Command;

use Dropcat\Services\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

class AboutCommand extends Command
{
    /** @var Configuration configuration */
    private $configuration;

    public function __construct(Configuration $conf)
    {
        $this->configuration = $conf;
        parent::__construct();
    }

    protected function configure()
    {
        $HelpText = '<info>Display the about</info>';

        $this->setName("about")
            ->setDescription("About dropcat")
            ->setHelp($HelpText);
    }
    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $style = new OutputFormatterStyle('black', 'green', array('blink', 'bold'));
        $output = new ConsoleOutput();

        $output->getFormatter()->setStyle('mjau', $style);

        $io->newLine(2);

        $output->writeln('<mjau>

          ____                              __
         / __ \_________  ____  _________ _/ /_
        / / / / ___/ __ \/ __ \/ ___/ __ `/ __/
       / /_/ / /  / /_/ / /_/ / /__/ /_/ / /_
      /_____/_/   \____/ .___/\___/\__,_/\__/
                      /__/

      Dropcat is a deploy tool for Drupal 8 sites, developed by Wunderkraut Sweden, say Mjau!
      </mjau>');
        $io->newLine(2);


    }
}
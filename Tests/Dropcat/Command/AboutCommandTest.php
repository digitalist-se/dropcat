<?php

namespace Dropcat\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Dropcat\Services\Configuration;

class AboutCommandTest extends TestCase
{

    /**
     * @return CommandTester
     */
    protected function createCommandTester($command)
    {
        $container = new ContainerBuilder();
        $container->set('DropcatContainer', $container);
        $conf = $configuration = new Configuration();
        $application = new Application();
        $application->add(new AboutCommand($container, $conf));
        $command = $application->find($command);
        return new CommandTester($command);
    }

    public function testExecute() {
        $tester = $this->createCommandTester('about');
        $tester->execute([]);
        $output = $tester->getDisplay();
        $this->assertIsString($output);
        $this->assertStringContainsString('digitalist', $output);
    }

    public function  testProcess() {
        $cmd = [
            'php',
            'app/dropcat',
            'about'
        ];
        $process = new Process($cmd);
        $process->mustRun();
        $output = $process->getOutput();
        $this->assertIsString($output);
        $this->assertStringContainsString('digitalist', $output);
    }
}

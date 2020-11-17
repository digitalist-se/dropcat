<?php

namespace Dropcat\Command;

use PharIo\Manifest\ApplicationName;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use PHPUnit\Framework\MockObject;



class SecurityDrupalCommandTest extends TestCase
{

    private $customerRepositoryMock;
    /** @var CommandTester */
    private $commandTester;

    protected function setUp()
    {

        // Create a stub for the SomeClass class.
        $stub = $this->getMockBuilder(SomeClass::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new CustomerCommand($this->customerRepositoryMock));
        $command = $application->find('customer');
        $this->commandTester = new CommandTester($command);
    }
}

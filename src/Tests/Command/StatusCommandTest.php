<?php

namespace Tests;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Console\StatusCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class StatusCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorExecute()
    {
        $application = new Application();
        $application->add(new StatusCommand());

        $command = $application->find('status');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
                'command' => $command->getName(),
                'sessionId' => 'error'
            ]
        );

        $this->assertRegExp('/This is for command test!/', $commandTester->getDisplay());

    }
}
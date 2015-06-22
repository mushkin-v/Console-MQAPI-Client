<?php

namespace Tests;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Console\DownloadCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DownloadCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorExecute()
    {
        $application = new Application();
        $application->add(new DownloadCommand());

        $command = $application->find('download');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
                'command' => $command->getName(),
                'sessionId' => 'error',
                'filepath' => 'error'
            ]
        );

        $this->assertRegExp('/This is for command test!/', $commandTester->getDisplay());

    }
}
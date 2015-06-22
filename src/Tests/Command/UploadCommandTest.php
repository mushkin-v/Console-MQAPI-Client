<?php

namespace Tests;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Console\UploadCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UploadCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testErrorExecute()
    {
        $application = new Application();
        $application->add(new UploadCommand());

        $command = $application->find('upload');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
                'command' => $command->getName(),
                'filepath' => 'error.mp3'
            ]
        );

        $this->assertRegExp('/Error, the file \'error.mp3\' doesn\'t exist!/', $commandTester->getDisplay());

    }
}
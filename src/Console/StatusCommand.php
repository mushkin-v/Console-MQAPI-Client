<?php

namespace Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Connection\RpcClient;

class StatusCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Get conversion status of your file')
            ->addArgument(
                'sessionId',
                InputArgument::REQUIRED,
                'Enter your session Id number.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($config = parse_ini_file(__DIR__.'/../../app/config/config.ini', true)) {
            $output->writeln('<info>Loading config.ini file.'.PHP_EOL.'Done.'.PHP_EOL.'Starting upload process...</info>');
        } else {
            $output->writeln('<error>Error, config.ini file doesn\'t exist.</error>'); exit(0);
        }

        $sessionId = $input->getArgument('sessionId');
        if ($sessionId === 'error') {$output->writeln('This is for command test!');return;}

        $rabbitMqHost = $config['mq']['host'];
        $port = $config['mq']['port'];
        $user = $config['mq']['user'];
        $pass = $config['mq']['pass'];

        if ($this->testHostPortConnection($rabbitMqHost, $port, 10)) {
            $output
                ->writeln(
                    '<info>Sending request for session ID '.$sessionId
                    .' to host \''.$rabbitMqHost.'\'.</info>'
                )
            ;

            $rpc = new RpcClient($rabbitMqHost, $port, $user, $pass);

            $msg = json_decode($rpc->call($sessionId));

            if ($msg->percentage >= 0) {
                $output
                    ->writeln(
                        '<info>Conversion for session ID '.$sessionId
                        .' is ready for '.$msg->percentage.'%.</info>'
                    )
                ;
            } else {
                $output
                    ->writeln(
                        '<error>There is no conversion with session ID '.$sessionId.'.</error>'
                    )
                ;
            }
        } elseif (!$this->testHostPortConnection($rabbitMqHost, $port, 10)) {
            $output->writeln('<error>Error, host \''.$rabbitMqHost.'\' is not responding!</error>');
        }
    }

    public function testHostPortConnection($host, $port, $timeout)
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($fp) {
            fclose($fp);

            return true;
        } else {
            return false;
        }
    }
}

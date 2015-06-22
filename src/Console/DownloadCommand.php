<?php

namespace Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Connection\RpcClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Helper\ProgressBar;

class DownloadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('download')
            ->setDescription('Download file from host.')
            ->addArgument(
                'sessionId',
                InputArgument::REQUIRED,
                'Enter your session Id number.'
            )
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'Enter directory name where you want to download file.'
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

        $filepath = $input->getArgument('filepath');
        if (substr($filepath, -1) !== '/') {
            $filepath .= '/';
        }

        $rabbitMqHost = $config['mq']['host'];
        $port = $config['mq']['port'];
        $user = $config['mq']['user'];
        $pass = $config['mq']['pass'];
        $api_download = $config['main']['api_download'];
        if (substr($api_download, -1) !== '/') {
            $api_download .= '/';
        }

        if ($this->testHostPortConnection($rabbitMqHost, $port, 10)) {
            $output
                ->writeln(
                    '<info>Sending request for session ID '.$sessionId
                    .' to host \''.$rabbitMqHost.'\'.</info>'
                )
            ;

            $rpc = new RpcClient($rabbitMqHost, $port, $user, $pass);

            $msg = json_decode($rpc->call($sessionId));

            if ($msg && $msg->percentage == 100) {
                $output
                    ->writeln(
                        '<info>Conversion for session ID '.$sessionId
                        .' is ready for '.$msg->percentage.'%.'.PHP_EOL
                        .'Starting download process...</info>'
                    )
                ;

                if (
                    is_dir($filepath)
                    && !file_exists($filepath.$msg->filename.'.'.$msg->newExt)
                ) {
                    try {
                        $progress = new ProgressBar($output, 3);
                        $progress->setFormat('<info> %current%/%max% [%bar%] %percent:3s%% </info>');
                        $progress->start();

                        $client = new Client(['base_uri' => $rabbitMqHost]);
                        $progress->setProgress(1);

                        $response = $client->get($api_download.$sessionId);
                        $progress->setProgress(2);

                        file_put_contents(
                            $filepath.$msg->filename.'.'.$msg->newExt,
                            $response->getBody()
                        );
                        $progress->setProgress(3);

                        $progress->finish();

                        $output
                            ->writeln(
                                PHP_EOL
                                .'<info>Download success.'.PHP_EOL
                                .'Your converted file was downloaded to '
                                .$filepath.$msg->filename.'.'.$msg->newExt.PHP_EOL
                                .'Thank you.</info>'
                            )
                        ;
                    } catch (RequestException $e) {
                        $output->writeln($e->getRequest());
                        if ($e->hasResponse()) {
                            $output->writeln($e->getResponse());
                        }
                    }
                } else {
                    $output
                        ->writeln(
                            '<error>Can\'t write file to '.$filepath.' from session ID '.$sessionId.'.'.PHP_EOL
                            .'No such directory or file already exists.</error>'
                        )
                    ;
                }
            } elseif ($msg && $msg->percentage >= 0 && $msg->percentage < 100) {
                $output->writeln(
                    '<comment>Conversion for session ID '.$sessionId
                    .' is ready for '.$msg->percentage
                    .'%, can\'t start downloading.</comment>'
                );
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

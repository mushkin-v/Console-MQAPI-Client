<?php

namespace Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Helper\ProgressBar;

class UploadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('upload')
            ->setDescription('Upload file to MQAPI for conversion.')
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'Enter path to file you want to upload.'
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

        $filepath = $input->getArgument('filepath');

        $rabbitMqHost = $config['mq']['host'];

        $port = $config['mq']['port'];
        $maxFileSize = $config['main']['max_file_size'];
        $fileTypes = $config['main']['file_types'];
        $api_upload = $config['main']['api_upload'];
        if (substr($api_upload, -1) === '/') {
            $api_upload = substr($api_upload, 0, -1);
        }

        $client = new Client(['base_uri' => $rabbitMqHost]);

        if (
            file_exists($filepath)
            && filesize($filepath) < $maxFileSize
            && in_array(pathinfo($filepath)['extension'], $fileTypes, true)
            && $this->testHostPortConnection($rabbitMqHost, $port, 10)
        ) {
            $file['ext'] = pathinfo($filepath)['extension'];
            $file['name'] = basename($filepath, '.'.$file['ext']);

            try {
                $progress = new ProgressBar($output, 3);
                $progress->setFormat('<info> %current%/%max% [%bar%] %percent:3s%% </info>');
                $progress->start();
                $progress->setProgress(1);

                $body = fopen($filepath, 'r');
                $progress->setProgress(2);

                $response = $client->post(
                    $api_upload, [
                        'query' => [
                            'filename' => $file['name'],
                            'ext' => $file['ext'],
                        ],
                        'multipart' => [
                            [
                                'name' => 'file',
                                'contents' => $body,
                            ],
                        ],
                    ]);
                $progress->setProgress(3);

                $progress->finish();

                if ($response->hasHeader('rabbitSesId')) {
                    $output
                        ->writeln(
                            PHP_EOL.'<info>Uploading of file \''.basename($filepath)
                            .'\' to host \''.$rabbitMqHost.'\' was successful. Your session ID is: '
                            .$response->getHeader('rabbitSesId')[0].'</info>'
                        )
                    ;
                } else {
                    $output
                        ->writeln(
                            '<error>The file \''.basename($filepath)
                            .'\' was uploaded, but host \''.$rabbitMqHost
                            .' didn\'t return your session ID!'.PHP_EOL
                            .'Please contact server administration for help.</error>'
                        )
                    ;
                }
            } catch (RequestException $e) {
                $output->writeln($e->getRequest()->getBody());
                if ($e->hasResponse()) {
                    $output->writeln($e->getResponse()->getBody());
                }
            }
        } elseif (!file_exists($filepath)) {
            $output->writeln('<error>Error, the file \''.$filepath.'\' doesn\'t exist!</error>');
        } elseif (filesize($filepath) > $maxFileSize) {
            $output->writeln('<error>Error, the file \''.$filepath.'\' size can\'t be bigger then '.round(($maxFileSize / pow(1024, 2)), 5).' megabytes!</error>');
        } elseif (!in_array(pathinfo($filepath)['extension'], $fileTypes, true)) {
            $output->writeln('<error>Error, wrong type of the file \''.$filepath.'\'!</error>');
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

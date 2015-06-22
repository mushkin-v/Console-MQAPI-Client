<?php

namespace Connection;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    public function __construct($host, $port, $user, $pass)
    {
        $this->connection = new AMQPConnection(
            $host, $port, $user, $pass);
        $this->channel = $this->connection->channel();
        list($this->callback_queue) = $this->channel->queue_declare(
            '', false, false, true, false);
        $this->channel->basic_consume(
            $this->callback_queue, '', false, false, false, false,
            array($this, 'on_response'));
    }

    public function on_response($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($sessionId)
    {
        $this->response = null;
        $this->corr_id = uniqid($sessionId, false);

        $msg = new AMQPMessage(
            (string) json_encode($sessionId),
            array('correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue, )
        );
        $this->channel->basic_publish($msg, '', 'rpc_consumer_queue');
        while (!$this->response) {
            $this->channel->wait();
        }

        return $this->response;
    }
}

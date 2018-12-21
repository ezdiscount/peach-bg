<?php

namespace app;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Rabbit
{
    const type = 'direct';

    private static $connection;
    private static $channel;

    static function getConnection()
    {
        if (empty(self::$connection)) {
            $f3 = \Base::instance();
            self::$connection = new AMQPStreamConnection(
                $f3->get('RMQ_HOST'),
                $f3->get('RMQ_PORT'),
                $f3->get('RMQ_USER'),
                $f3->get('RMQ_PASS')
            );
        }
        return self::$connection;
    }

    static function getChannel()
    {
        if (empty(self::$channel)) {
            self::$channel = self::getConnection()->channel();
        }
        return self::$channel;
    }

    static function send(string $exchange, string $message)
    {
        // declare an exchange to publish messages
        $channel = self::getChannel();
        $channel->exchange_declare($exchange, self::type);
        $channel->basic_publish(new AMQPMessage($message), $exchange);
    }

    static function consume(string $exchange, string $queue, string $tag, callable $callback)
    {
        // declare a queue to consume from
        $channel = self::getChannel();
        $channel->exchange_declare($exchange, self::type);
        $channel->queue_declare($queue, false, false, true, true);
        $channel->queue_bind($queue, $exchange);
        $channel->basic_consume($queue, $tag, false, false, true, false, $callback);
    }

    static function disconnect()
    {
        if (!empty(self::$channel)) {
            self::$channel->close();
        }
        if (!empty(self::$connection)) {
            self::$connection->close();
        }
    }
}

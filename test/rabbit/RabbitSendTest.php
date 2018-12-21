<?php

namespace test\rabbit;

use app\Rabbit;
use PHPUnit\Framework\TestCase;

class RabbitSendTest extends TestCase
{
    function test()
    {
        Rabbit::send('peachExchange', 'test message');
        $this->assertTrue(true);
    }
}

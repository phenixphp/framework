<?php

declare(strict_types=1);

use Amp\Redis\Connection\RedisLink;
use Amp\Redis\Protocol\RedisResponse;
use Amp\Redis\RedisClient;
use Phenix\Redis\Client;

it('executes a Redis command', function (): void {
    $linkMock = $this->getMockBuilder(RedisLink::class)
        ->disableOriginalConstructor()
        ->getMock();

    $linkMock->expects($this->once())
        ->method('execute')
        ->with('PING')
        ->willReturn($this->createMock(RedisResponse::class));

    $redis = new RedisClient($linkMock);

    $client = new Client($redis);
    $client->execute('PING');
});

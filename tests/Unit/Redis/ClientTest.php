<?php

declare(strict_types=1);

use Amp\Redis\Connection\RedisLink;
use Amp\Redis\Protocol\RedisResponse;
use Amp\Redis\RedisClient;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Redis;
use Phenix\Redis\ClientWrapper;
use Phenix\Redis\Exceptions\UnknownConnection;

it('executes a redis command using client wrapper', function (): void {
    $linkMock = $this->getMockBuilder(RedisLink::class)
        ->disableOriginalConstructor()
        ->getMock();

    $linkMock->expects($this->once())
        ->method('execute')
        ->with('PING')
        ->willReturn($this->createMock(RedisResponse::class));

    $redis = new RedisClient($linkMock);

    $client = new ClientWrapper($redis);
    $client->execute('PING');
});

it('executes a redis command using facade', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->once())
        ->method('execute')
        ->with('PING')
        ->willReturn($this->createMock(RedisResponse::class));

    $this->app->swap(Connection::redis('default'), $client);

    Redis::execute('PING');
});

it('throws an exception when connection is not configured', function (): void {
    Redis::connection('invalid-connection');
})->throws(UnknownConnection::class, 'Redis connection [invalid-connection] not configured.');

it('changes the redis connection using facade', function (): void {
    $clientDefault = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $clientDefault->expects($this->once())
        ->method('execute')
        ->with('PING')
        ->willReturn($this->createMock(RedisResponse::class));

    $this->app->swap(Connection::redis('default'), $clientDefault);

    Redis::connection('default')->execute('PING');
});

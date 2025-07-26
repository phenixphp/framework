<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Redis\Client;
use Phenix\Redis\Contracts\Client as ClientContract;
use Tests\Unit\Queue\Tasks\SampleQueuableTask;

beforeEach(function (): void {
    Config::set('queue.default', QueueDriver::REDIS->value);
});

it('pushes a task onto the Redis queue', function (): void {
    $clientMock = $this->getMockBuilder(Client::class)
        ->disableOriginalConstructor()
        ->getMock();

    $clientMock->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('RPUSH'),
            $this->equalTo('queues:default'),
            $this->isType('string') // Assuming payload is serialized to a string
        )
        ->willReturn(true);

    $this->app->swap(ClientContract::class, $clientMock);

    SampleQueuableTask::dispatch();
});

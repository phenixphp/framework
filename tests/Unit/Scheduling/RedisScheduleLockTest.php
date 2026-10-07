<?php

declare(strict_types=1);

use Phenix\Redis\ClientWrapper;
use Phenix\Scheduling\RedisScheduleLock;

it('acquires a named occurrence with one atomic Redis script', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();

    $client->expects($this->once())->method('execute')->with(
        'EVAL',
        $this->stringContains("redis.call('SET'"),
        1,
        'test:schedule:' . hash('sha256', 'reconcile-usage') . ':202610061430',
        86400,
        $this->isType('string')
    )->willReturn(1);

    $lock = new RedisScheduleLock($client, 'test:schedule:');

    expect($lock->acquire('reconcile-usage', '202610061430', 86400))->toBeString();
});

it('reports an occurrence already acquired by another scheduler', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $client->expects($this->once())->method('execute')->willReturn(0);

    $lock = new RedisScheduleLock($client);

    expect($lock->acquire('reconcile-usage', '202610061430', 86400))->toBeNull();
});

it('releases only the lock owned by the supplied token', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)->disableOriginalConstructor()->getMock();
    $client->expects($this->once())->method('execute')->with(
        'EVAL',
        $this->stringContains("redis.call('GET'"),
        1,
        'test:schedule:' . hash('sha256', 'reconcile-usage') . ':202610061430',
        'owner-token'
    )->willReturn(1);

    (new RedisScheduleLock($client, 'test:schedule:'))
        ->release('reconcile-usage', '202610061430', 'owner-token');
});

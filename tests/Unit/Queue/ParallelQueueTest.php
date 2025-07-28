<?php

declare(strict_types=1);

use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Facades\Config;
use Tests\Unit\Queue\Tasks\SampleQueuableTask;

beforeEach(function (): void {
    Config::set('queue.default', QueueDriver::PARALLEL->value);
});

it('pushes a task onto the parallel queue', function () {
    Queue::clear();
    Queue::push(new SampleQueuableTask());

    $task = Queue::pop();

    $this->assertNotNull($task);
    $this->assertInstanceOf(SampleQueuableTask::class, $task);
});

it('pushes a task onto a custom parallel queue', function () {
    Queue::clear();
    Queue::pushOn('custom-parallel', new SampleQueuableTask());

    $task = Queue::pop('custom-parallel');

    $this->assertNotNull($task);
    $this->assertInstanceOf(SampleQueuableTask::class, $task);
});

it('returns the correct size for parallel queue', function () {
    Queue::clear();
    Queue::push(new SampleQueuableTask());

    $this->assertSame(1, Queue::size());

    Queue::pop();

    $this->assertSame(0, Queue::size());
});

it('clears the parallel queue', function () {
    Queue::push(new SampleQueuableTask());
    Queue::clear();

    $this->assertSame(0, Queue::size());
});

it('gets and sets the connection name for parallel queue', function () {
    Queue::setConnectionName('parallel-connection');

    $this->assertSame('parallel-connection', Queue::getConnectionName());
});

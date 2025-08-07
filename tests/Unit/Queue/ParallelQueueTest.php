<?php

declare(strict_types=1);

use Amp\Future;
use Phenix\Facades\Config;
use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\ParallelQueue;
use Tests\Unit\Queue\Tasks\SampleQueuableTask;

use function Amp\async;
use function Amp\delay;

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

it('automatically starts processing when tasks are added', function () {
    $parallelQueue = new ParallelQueue('test-auto-start');

    // Queue should initially be without processing
    $this->assertFalse($parallelQueue->isProcessing());

    // Adding a task should enable processing
    $parallelQueue->push(new SampleQueuableTask());

    $this->assertTrue($parallelQueue->isProcessing());
    $this->assertSame(1, $parallelQueue->size());
});

it('can manually start and stop processing', function () {
    $parallelQueue = new ParallelQueue('test-manual-control');

    // Add tasks without automatic processing
    $parallelQueue->push(new SampleQueuableTask());
    $parallelQueue->stop(); // Stop automatic processing

    $this->assertFalse($parallelQueue->isProcessing());

    // Start manually
    $parallelQueue->start();
    $this->assertTrue($parallelQueue->isProcessing());

    // Stop manually
    $parallelQueue->stop();
    $this->assertFalse($parallelQueue->isProcessing());
});

it('processes tasks using Interval without blocking', function () {
    $parallelQueue = new ParallelQueue('test-interval');

    // Add multiple tasks
    for ($i = 0; $i < 5; $i++) {
        $parallelQueue->push(new SampleQueuableTask());
    }

    $this->assertSame(5, $parallelQueue->size());
    $this->assertTrue($parallelQueue->isProcessing());

    // Processing with Interval should not block
    // This should complete immediately without blocking
    $startTime = microtime(true);

    // Give time for Interval to process some tasks
    delay(0.5);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Execution should take approximately 0.5 seconds (without blocking)
    $this->assertLessThan(1.0, $executionTime);

    // Some tasks may have been processed
    $this->assertLessThanOrEqual(5, $parallelQueue->size());
});

it('automatically stops processing when no tasks remain', function () {
    $parallelQueue = new ParallelQueue('test-auto-stop');

    // Add a task
    $parallelQueue->push(new SampleQueuableTask());
    $this->assertTrue($parallelQueue->isProcessing());

    // Give enough time to process all tasks
    delay(6.0);

    // Processing should have stopped automatically
    $this->assertFalse($parallelQueue->isProcessing());

    // There should be no pending tasks
    $this->assertSame(0, $parallelQueue->getRunningTasksCount());
});

it('provides detailed processor status', function () {
    $parallelQueue = new ParallelQueue('test-status');

    // Initial state
    $status = $parallelQueue->getProcessorStatus();
    $this->assertArrayHasKey('is_processing', $status);
    $this->assertArrayHasKey('pending_tasks', $status);
    $this->assertArrayHasKey('running_tasks', $status);
    $this->assertArrayHasKey('max_concurrency', $status);
    $this->assertArrayHasKey('total_tasks', $status);

    $this->assertFalse($status['is_processing']);
    $this->assertSame(0, $status['pending_tasks']);
    $this->assertSame(0, $status['running_tasks']);
    $this->assertSame(0, $status['total_tasks']);

    // After adding tasks
    $parallelQueue->push(new SampleQueuableTask());
    $parallelQueue->push(new SampleQueuableTask());

    $status = $parallelQueue->getProcessorStatus();
    $this->assertTrue($status['is_processing']);
    $this->assertSame(2, $status['total_tasks']);
});

it('works correctly with the HTTP server without blocking', function () {
    $parallelQueue = new ParallelQueue('test-http-compat');

    // Simulate multiple concurrent operations like in an HTTP server
    $futures = [];

    // Simulate multiple requests that add tasks
    for ($i = 0; $i < 10; $i++) {
        $futures[] = async(function () use ($parallelQueue, $i) {
            $parallelQueue->push(new SampleQueuableTask());

            // Simulate request work
            delay(0.1);

            return "Request {$i} completed";
        });
    }

    // This should complete without blocking
    $startTime = microtime(true);

    // Wait for all "requests" to finish
    Future\await($futures);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Should execute concurrently, not sequentially
    $this->assertLessThan(1.0, $executionTime); // Much less than 10 * 0.1s

    // Verify that tasks were added
    $this->assertSame(10, $parallelQueue->size());
    $this->assertTrue($parallelQueue->isProcessing());
});

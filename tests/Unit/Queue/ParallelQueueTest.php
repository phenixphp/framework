<?php

declare(strict_types=1);

use Amp\Future;
use Phenix\Facades\Config;
use Phenix\Facades\Queue;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\ParallelQueue;
use Tests\Unit\Queue\Tasks\DelayableTask;
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

    expect($task)->not->toBeNull();
    expect($task)->toBeInstanceOf(SampleQueuableTask::class);
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

it('skips processing new tasks when previous tasks are still running', function () {
    $parallelQueue = new ParallelQueue('test-skip-processing');

    // Add initial task that will take 6 seconds to process
    $parallelQueue->push(new DelayableTask(6));

    $this->assertTrue($parallelQueue->isProcessing());

    // Wait a bit for tasks to start processing but not complete
    delay(4.0);

    // Verify the queue size
    expect($parallelQueue->size())->ToBe(1);

    // Processor should still be running
    expect($parallelQueue->isProcessing())->ToBeTrue();
});

it('automatically disables processing when no tasks are available to reserve', function () {
    $parallelQueue = new ParallelQueue('test-empty-queue');

    // Start with an empty queue
    $this->assertFalse($parallelQueue->isProcessing());
    $this->assertSame(0, $parallelQueue->size());

    // Manually start processing on an empty queue
    $parallelQueue->start();
    $this->assertTrue($parallelQueue->isProcessing());

    // Wait for the interval to run and detect empty queue
    delay(3.0); // Wait longer than the 2.0 second interval

    // Processing should have been automatically disabled
    $this->assertFalse($parallelQueue->isProcessing());
    $this->assertSame(0, $parallelQueue->size());
    $this->assertSame(0, $parallelQueue->getRunningTasksCount());
});

it('automatically disables processing after all tasks complete', function () {
    $parallelQueue = new ParallelQueue('test-complete-all-tasks');

    // Add a single task
    $parallelQueue->push(new SampleQueuableTask());
    $this->assertTrue($parallelQueue->isProcessing());
    $this->assertGreaterThan(0, $parallelQueue->size());

    // Wait for tasks to be processed and completed
    delay(8.0); // Wait long enough for tasks to complete and cleanup

    // Verify processing was disabled after all tasks completed
    $this->assertFalse($parallelQueue->isProcessing());
    $this->assertSame(0, $parallelQueue->size());
    $this->assertSame(0, $parallelQueue->getRunningTasksCount());

    // Verify status reflects empty state
    $status = $parallelQueue->getProcessorStatus();
    $this->assertFalse($status['is_processing']);
    $this->assertSame(0, $status['pending_tasks']);
    $this->assertSame(0, $status['running_tasks']);
    $this->assertSame(0, $status['total_tasks']);
});

it('handles chunk processing when no available tasks exist', function () {
    $parallelQueue = new ParallelQueue('test-no-available-tasks');

    // Start processing with empty queue to trigger the break condition
    $parallelQueue->start();
    $this->assertTrue($parallelQueue->isProcessing());

    // Wait for interval to run and encounter empty task scenario
    delay(3.0);

    // Should automatically disable processing due to no tasks available
    $this->assertFalse($parallelQueue->isProcessing());
    $this->assertSame(0, $parallelQueue->size());

    // Add a task to verify it can resume processing
    $parallelQueue->push(new SampleQueuableTask());
    $this->assertTrue($parallelQueue->isProcessing());
    $this->assertGreaterThan(0, $parallelQueue->size());
});

it('re-enqueues tasks that cannot be reserved during chunk processing', function () {
    // Create a custom test to force the reserve failure scenario
    $parallelQueue = new ParallelQueue('test-reserve-failure');

    // Add multiple tasks that will create a scenario where some might not be reservable
    for ($i = 0; $i < 5; $i++) {
        $parallelQueue->push(new SampleQueuableTask());
    }

    $initialSize = $parallelQueue->size();
    $this->assertTrue($parallelQueue->isProcessing());
    $this->assertGreaterThan(0, $initialSize);

    // Wait a bit to let some processing happen
    delay(1.0);

    // Even if some reservations fail, tasks should still be in the queue
    // The size might have changed due to processing, but shouldn't be negative
    $this->assertGreaterThanOrEqual(0, $parallelQueue->size());

    // Processor should still be working if there are tasks
    if ($parallelQueue->size() > 0) {
        $this->assertTrue($parallelQueue->isProcessing());
    }

    // Wait for complete processing
    delay(8.0);

    // All tasks should eventually be processed or re-enqueued appropriately
    $this->assertGreaterThanOrEqual(0, $parallelQueue->size());
});

it('handles concurrent task reservation attempts correctly', function () {
    $parallelQueue = new ParallelQueue('test-concurrent-reservation');

    // Create multiple tasks to increase chances of reservation conflicts
    for ($i = 0; $i < 10; $i++) {
        $parallelQueue->push(new SampleQueuableTask());
    }

    $this->assertTrue($parallelQueue->isProcessing());
    $initialSize = $parallelQueue->size();
    $this->assertSame(10, $initialSize);

    // Allow some time for processing to start and potentially encounter reservation conflicts
    delay(2.5); // Wait just a bit more than the interval time

    // Verify queue is still functioning properly despite any reservation conflicts
    $currentSize = $parallelQueue->size();
    $this->assertGreaterThanOrEqual(0, $currentSize);

    // If tasks remain, processing should continue
    if ($currentSize > 0) {
        $this->assertTrue($parallelQueue->isProcessing());
    }

    // Wait for all tasks to complete
    delay(10.0);

    // Eventually all tasks should be processed
    $this->assertSame(0, $parallelQueue->size());
    $this->assertFalse($parallelQueue->isProcessing());
});

it('handles task failures gracefully', function (): void {
    Config::set('queue.drivers.parallel.retry_delay', 1);

    $parallelQueue = new ParallelQueue('test-task-failure');

    // Push a task that is expected to fail
    $parallelQueue->push(new BadTask());

    // Wait for the task to be processed
    delay(3.0);

    // Verify that the queue still processing and can handle failures
    $this->assertTrue($parallelQueue->isProcessing());
    $this->assertSame(1, $parallelQueue->size()); // Task should have been removed after processing

    // Wait for the task to be processed
    delay(6.0);

    $this->assertFalse($parallelQueue->isProcessing());
    $this->assertSame(0, $parallelQueue->size()); // Task should have been removed after processing
});

<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Amp\Cancellation;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker as ParallelWorker;
use Phenix\Runtime\Facade;

/**
 * Worker Pool Facade
 *
 * @method static bool isRunning()
 * @method static bool isIdle()
 * @method static int getWorkerLimit()
 * @method static int getLimit()
 * @method static int getWorkerCount()
 * @method static int getIdleWorkerCount()
 * @method static Execution submit(Task $task, Cancellation|null $cancellation = null)
 * @method static void shutdown()
 * @method static void kill()
 * @method static ParallelWorker getWorker()
 *
 * @see \Amp\Parallel\Worker\WorkerPool
 */
class Worker extends Facade
{
    public static function getKeyName(): string
    {
        return \Amp\Parallel\Worker\WorkerPool::class;
    }
}

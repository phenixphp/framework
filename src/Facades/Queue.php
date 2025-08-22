<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\Contracts\Queue as QueueContract;
use Phenix\Queue\QueueManager;
use Phenix\Runtime\Facade;
use Phenix\Tasks\QueuableTask;

/**
 * @method static void push(QueuableTask $task)
 * @method static void pushOn(string $queue, QueuableTask $task)
 * @method static QueuableTask|null pop()
 * @method static array<int, QueuableTask> popChunk(int $limit = 10, string|null $queueName = null)
 * @method static int size()
 * @method static void clear()
 * @method static string getConnectionName()
 * @method static void setConnectionName(string $name)
 * @method static QueueContract driver(QueueDriver|null $driverName = null)
 *
 * @see \Phenix\Queue\QueueManager
 */
class Queue extends Facade
{
    protected static function getKeyName(): string
    {
        return QueueManager::class;
    }
}

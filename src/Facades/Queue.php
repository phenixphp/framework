<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Tasks\QueuableTask;
use Phenix\Runtime\Facade;
use Phenix\Queue\QueueManager;

/**
 * @method static void push(QueuableTask $task)
 * @method static void pushOn(string $queue, QueuableTask $task)
 * @method static QueuableTask|null pop()
 * @method static int size()
 * @method static void clear()
 * @method static string getConnectionName()
 * @method static void setConnectionName(string $name)
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

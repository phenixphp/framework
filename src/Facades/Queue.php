<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Tasks\QueuableTask;
use Phenix\Runtime\Facade;
use Phenix\Queue\QueueManager;

/**
 * @method static void push(QueuableTask $job)
 * @method static void pushOn(string $queue, QueuableTask $job)
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

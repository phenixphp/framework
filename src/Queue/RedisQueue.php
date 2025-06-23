<?php

declare(strict_types=1);

namespace Phenix\Queue;

class RedisQueue extends Queue
{
    public function __construct(
        protected string $connection = 'default',
        protected string|null $queueName = 'default'
    ) {
        $this->connectionName = $connection;
    }

    // TODO: Implementar métodos específicos de Redis
}

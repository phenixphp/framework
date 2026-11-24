<?php

declare(strict_types=1);

namespace Phenix\Filesystem\Tasks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

/** @implements Task<string|false, mixed, mixed> */
class ResolvePath implements Task
{
    public function __construct(private string $path)
    {
    }

    public function run(Channel $channel, Cancellation $cancellation): string|false
    {
        $cancellation->throwIfRequested();

        return realpath($this->path);
    }
}

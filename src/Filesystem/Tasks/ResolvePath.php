<?php

declare(strict_types=1);

namespace Phenix\Filesystem\Tasks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Phenix\Tasks\Result;

/** @implements Task<Result, mixed, mixed> */
class ResolvePath implements Task
{
    public function __construct(private string $path)
    {
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        $cancellation->throwIfRequested();
        clearstatcache(true);

        return Result::success(realpath($this->path));
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Hasher;
use Phenix\Tasks\Result;
use Phenix\Tasks\Task;

class CheckNeedsRehash extends Task
{
    public function __construct(
        protected string $hash,
    ) {}

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $hasher = new Hasher();

        return Result::success(
            $hasher->needsRehash($this->hash)
        );
    }
}

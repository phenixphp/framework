<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Hasher;
use Phenix\Tasks\Result;
use Phenix\Tasks\Task;
use SensitiveParameter;

class GeneratePasswordHash extends Task
{
    public function __construct(
        #[SensitiveParameter]
        protected string $password
    ) {}

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $hasher = new Hasher();

        return Result::success(
            $hasher->make($this->password)
        );
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Hasher;
use Phenix\Tasks\ParallelTask;
use Phenix\Tasks\Result;
use SensitiveParameter;

class VerifyPasswordHash extends ParallelTask
{
    public function __construct(
        protected string $hash,
        #[SensitiveParameter]
        protected string $password
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $hasher = new Hasher();

        return Result::success(
            $hasher->verify($this->hash, $this->password)
        );
    }
}

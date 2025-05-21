<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Hasher;
use Phenix\Tasks\Task;
use Phenix\Tasks\Result;
use SensitiveParameter;

class GeneratePasswordHash extends Task
{
    public function __construct(
        #[SensitiveParameter]
        protected string $password
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $hasher = new Hasher();

        return Result::success(
            $hasher->make($this->password)
        );
    }
}

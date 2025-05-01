<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Cipher;
use Phenix\Tasks\AppParallelTask;
use Phenix\Tasks\Result;
use Throwable;

class Encrypt extends AppParallelTask
{
    public function __construct(
        protected string $key,
        protected object|array|string $value,
        protected bool $serialize = true
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $cipher = new Cipher($this->key);

        try {
            $output = $cipher->encrypt($this->value, $this->serialize);

            return Result::success($output);
        } catch (Throwable $th) {
            report($th);

            return Result::failure(message: $th->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Cipher;
use Phenix\Tasks\ParallelTask;
use Phenix\Tasks\Result;
use Throwable;

class Decrypt extends ParallelTask
{
    public function __construct(
        protected string $key,
        protected string $value,
        protected bool $unserialize = true
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $cipher = new Cipher($this->key);

        try {
            $output = $cipher->decrypt($this->value, $this->unserialize);

            return Result::success($output);
        } catch (Throwable $th) {
            report($th);

            return Result::failure(message: $th->getMessage());
        }
    }
}

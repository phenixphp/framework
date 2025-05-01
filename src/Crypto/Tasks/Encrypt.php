<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Cipher;
use Phenix\Tasks\ParallelTask;
use Phenix\Tasks\Result;
use Throwable;
use SensitiveParameter;

class Encrypt extends ParallelTask
{
    public function __construct(
        #[SensitiveParameter]
        protected string $key,
        #[SensitiveParameter]
        protected object|array|string $value,
        protected bool $serialize = true
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $cipher = new Cipher($this->key);

            $output = $cipher->encrypt($this->value, $this->serialize);

            return Result::success($output);
        } catch (Throwable $th) {
            report($th);

            $className = $this::class;

            $message = "[{$className}] {$th->getMessage()}";

            return Result::failure(message: $message);
        }
    }
}

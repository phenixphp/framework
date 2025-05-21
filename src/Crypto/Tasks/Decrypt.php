<?php

declare(strict_types=1);

namespace Phenix\Crypto\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Cipher;
use Phenix\Tasks\Task;
use Phenix\Tasks\Result;
use SensitiveParameter;
use Throwable;

class Decrypt extends Task
{
    public function __construct(
        #[SensitiveParameter]
        protected string $key,
        #[SensitiveParameter]
        protected string $value,
        protected bool $unserialize = true,
        #[SensitiveParameter]
        protected string|null $previousKey = null,
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $cipher = new Cipher($this->key, $this->previousKey);

            $output = $cipher->decrypt($this->value, $this->unserialize);

            return Result::success($output);
        } catch (Throwable $th) {
            report($th);

            $className = $this::class;

            $message = "[{$className}] {$th->getMessage()}";

            return Result::failure(message: $message);
        }
    }
}

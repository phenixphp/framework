<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Phenix\Tasks\Contracts\Result as ContractsResult;

class Result implements ContractsResult
{
    protected bool $status;

    private function __construct(
        protected mixed $output = null,
        protected string|null $message = null,
        bool $status = true
    ) {
        $this->status = $status;
    }

    public function output(): mixed
    {
        return $this->output;
    }

    public function message(): string|null
    {
        return $this->message;
    }

    public function isSuccess(): bool
    {
        return $this->status === true;
    }

    public function isFailure(): bool
    {
        return ! $this->isSuccess();
    }

    public static function success(mixed $output = null, string|null $message = null): static
    {
        return new static($output, $message);
    }

    public static function failure(mixed $output = null, string|null $message = null): static
    {
        return new static($output, $message, false);
    }
}

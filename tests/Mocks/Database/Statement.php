<?php

declare(strict_types=1);

namespace Tests\Mocks\Database;

use Amp\Sql\SqlResult;
use Amp\Sql\SqlStatement;
use Closure;
use Tests\Mocks\Database\Result as FakeResult;

class Statement implements SqlStatement
{
    public function __construct(protected FakeResult $fakeResult)
    {
        // ..
    }

    public function execute(array $params = []): SqlResult
    {
        return $this->fakeResult;
    }

    public function getQuery(): string
    {
        return 'SQL';
    }

    public function getLastUsedAt(): int
    {
        return time();
    }

    public function close(): void
    {
        // Intentionally no-code method
    }

    public function isClosed(): bool
    {
        return true;
    }

    public function onClose(Closure $onClose): void
    {
        $onClose();
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Sql\SqlTransaction;

class TransactionNode
{
    public function __construct(
        public readonly SqlTransaction $transaction,
        public readonly TransactionNode|null $parent,
        public readonly int $depth,
        public readonly float $startedAt,
    ) {
    }

    public function isRoot(): bool
    {
        return $this->transaction->getSavepointIdentifier() === null;
    }

    public function hasSavepoint(): bool
    {
        return $this->transaction->getSavepointIdentifier() !== null;
    }

    public function getSavepointIdentifier(): string|null
    {
        return $this->transaction->getSavepointIdentifier();
    }

    public function isActive(): bool
    {
        return $this->transaction->isActive();
    }

    public function age(): float
    {
        return microtime(true) - $this->startedAt;
    }
}

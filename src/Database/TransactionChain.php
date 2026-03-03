<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Sql\SqlTransaction;

class TransactionChain
{
    protected TransactionNode|null $current = null;

    public function push(SqlTransaction $transaction): void
    {
        $this->current = new TransactionNode(
            transaction: $transaction,
            parent: $this->current,
            depth: $this->current !== null ? $this->current->depth + 1 : 0,
            startedAt: microtime(true),
        );
    }

    public function pop(): TransactionNode|null
    {
        $popped = $this->current;

        $this->current = $this->current?->parent;

        return $popped;
    }

    public function current(): TransactionNode|null
    {
        return $this->current;
    }

    public function root(): TransactionNode|null
    {
        $node = $this->current;

        while ($node?->parent !== null) {
            $node = $node->parent;
        }

        return $node;
    }

    public function depth(): int
    {
        return $this->current !== null ? $this->current->depth + 1 : 0;
    }

    public function isEmpty(): bool
    {
        return $this->current === null;
    }

    /**
     * @return array<int, TransactionNode>
     */
    public function all(): array
    {
        $nodes = [];
        $node = $this->current;

        while ($node !== null) {
            array_unshift($nodes, $node);
            $node = $node->parent;
        }

        return $nodes;
    }

    /**
     * @return array<int, TransactionNode>
     */
    public function getLongRunning(float $threshold = 5.0): array
    {
        return array_filter(
            $this->all(),
            fn (TransactionNode $node): bool => $node->age() > $threshold
        );
    }
}

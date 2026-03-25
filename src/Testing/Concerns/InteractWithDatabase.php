<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Closure;
use Phenix\Facades\DB;
use PHPUnit\Framework\Assert;

trait InteractWithDatabase
{
    /**
     * @param  Closure|array<string, scalar|bool|int|string|null>  $criteria
     */
    public function assertDatabaseHas(string $table, Closure|array $criteria): void
    {
        $count = $this->getRecordCount($table, $criteria);

        Assert::assertGreaterThan(0, $count, 'Failed asserting that table has matching record.');
    }

    /**
     * @param  Closure|array<string, scalar|bool|int|string|null>  $criteria
     */
    public function assertDatabaseMissing(string $table, Closure|array $criteria): void
    {
        $count = $this->getRecordCount($table, $criteria);

        Assert::assertSame(0, $count, 'Failed asserting that table is missing the provided record.');
    }

    /**
     * @param  Closure|array<string, scalar|bool|int|string|null>  $criteria
     */
    public function assertDatabaseCount(string $table, int $expected, Closure|array $criteria = []): void
    {
        $count = $this->getRecordCount($table, $criteria);

        Assert::assertSame($expected, $count, 'Failed asserting the expected database record count.');
    }

    /**
     * @param  Closure|array<string, scalar|bool|int|string|null>  $criteria
     */
    protected function getRecordCount(string $table, Closure|array $criteria): int
    {
        $query = DB::from($table);

        if ($criteria instanceof Closure) {
            $criteria($query);

            return $query->count();
        }

        foreach ($criteria as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);

                continue;
            }

            if (is_bool($value)) {
                $value = (int) $value; // normalize boolean to int representation
            }

            $query->whereEqual($column, is_int($value) ? $value : (string) $value);
        }

        return $query->count();
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Phenix\Facades\DB;
use PHPUnit\Framework\Assert;

trait InteractWithDatabase
{
    /**
     * @param  array<string, scalar|bool|int|string|null>  $data
     */
    public function assertDatabaseHas(string $table, array $data): void
    {
        $count = $this->getRecordCount($table, $data);

        Assert::assertGreaterThan(0, $count, 'Failed asserting that table has matching record.');
    }

    /**
     * @param  array<string, scalar|bool|int|string|null>  $data
     */
    public function assertDatabaseMissing(string $table, array $data): void
    {
        $count = $this->getRecordCount($table, $data);

        Assert::assertSame(0, $count, 'Failed asserting that table is missing the provided record.');
    }

    /**
     * @param  array<string, scalar|bool|int|string|null>  $data
     */
    public function assertDatabaseCount(string $table, int $expected, array $data = []): void
    {
        $count = $this->getRecordCount($table, $data);

        Assert::assertSame($expected, $count, 'Failed asserting the expected database record count.');
    }

    /**
     * @param  array<string, scalar|bool|int|string|null>  $data
     */
    protected function getRecordCount(string $table, array $data): int
    {
        $query = DB::from($table);

        foreach ($data as $column => $value) {
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

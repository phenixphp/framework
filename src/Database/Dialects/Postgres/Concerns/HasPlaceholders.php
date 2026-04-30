<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Concerns;

trait HasPlaceholders
{
    // TODO: Refactor this to be more efficient and handle edge cases
    protected function convertPlaceholders(string $sql, int $startIndex = 0): string
    {
        $index = $startIndex + 1;

        return preg_replace_callback(
            '/\?/',
            fn (): string => '$' . ($index++),
            $sql
        );
    }

    protected function normalizePlaceholders(string $sql, int $startIndex = 0): string
    {
        $index = $startIndex + 1;

        return preg_replace_callback(
            '/\?|\$\d+/',
            fn (): string => '$' . ($index++),
            $sql
        );
    }
}

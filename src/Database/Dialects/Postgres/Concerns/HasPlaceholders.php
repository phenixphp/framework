<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Concerns;

trait HasPlaceholders
{
    protected function convertPlaceholders(string $sql, int $startIndex = 0): string
    {
        $index = $startIndex + 1;

        return preg_replace_callback(
            '/\?/',
            function () use (&$index): string {
                return '$' . ($index++);
            },
            $sql
        );
    }
}

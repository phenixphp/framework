<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL\Concerns;

trait HasPlaceholders
{
    protected function convertPlaceholders(string $sql): string
    {
        $index = 1;

        return preg_replace_callback(
            '/\?/',
            function () use (&$index): string {
                return '$' . ($index++);
            },
            $sql
        );
    }
}

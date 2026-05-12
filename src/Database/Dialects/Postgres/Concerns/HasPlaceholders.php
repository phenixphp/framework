<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Concerns;

use Phenix\Database\Constants\SqlMark;

trait HasPlaceholders
{
    protected function replacePlaceholders(string $sql, int $startIndex = 0): string
    {
        $index = $startIndex + 1;

        return preg_replace_callback(
            '/\{\?\}/',
            function () use (&$index): string {
                $placeholder = "\${$index}";

                $index++;

                return $placeholder;
            },
            $sql
        );
    }

    protected function resetPlaceholders(string $sql): string
    {
        return preg_replace('/\$\d+/', SqlMark::Placeholder->value, $sql);
    }
}

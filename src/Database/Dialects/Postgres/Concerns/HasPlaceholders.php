<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Concerns;

use Phenix\Database\Constants\SqlMode;

trait HasPlaceholders
{
    protected function replacePlaceholders(string $sql, int $startIndex = 0): string
    {
        if ($this->sqlMode === SqlMode::Raw) {
            return $sql;
        }

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
}

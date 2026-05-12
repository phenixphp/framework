<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects;

readonly class SqlData
{
    /**
     * @param string $sql The compiled SQL string
     * @param array<int, mixed> $params The parameters for prepared statements
     */
    public function __construct(
        public string $sql,
        public array $params = []
    ) {
    }
}

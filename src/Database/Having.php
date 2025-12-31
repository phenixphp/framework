<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Constants\SQL;

class Having extends Clause
{
    public function __construct()
    {
        $this->clauses = [];
        $this->arguments = [];
    }

    public function toSql(): array
    {
        if (empty($this->clauses)) {
            return ['', []];
        }

        $sql = [];

        foreach ($this->clauses as $clause) {
            $clauseSql = "{$clause->getColumn()} {$clause->getOperator()->value} " . SQL::PLACEHOLDER->value;

            if ($connector = $clause->getConnector()) {
                $clauseSql = "{$connector->value} {$clauseSql}";
            }

            $sql[] = $clauseSql;
        }

        return ['HAVING ' . implode(' ', $sql), $this->arguments];
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Clauses\DateWhereClause;
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
        $sql = [];

        foreach ($this->clauses as $clause) {
            $column = Wrapper::column($this->driver, $clause->getColumn());

            if ($clause instanceof DateWhereClause) {
                $function = $clause->getFunction()->name;
                $column = "{$function}({$column})";
            }

            $clauseSql = "{$column} {$clause->getOperator()->value} " . SQL::PLACEHOLDER->value;

            if ($connector = $clause->getConnector()) {
                $clauseSql = "{$connector->value} {$clauseSql}";
            }

            $sql[] = $clauseSql;
        }

        return ['HAVING ' . implode(' ', $sql), $this->arguments];
    }
}

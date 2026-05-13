<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Constants\SqlMode;

class Subquery extends QueryGenerator
{
    protected string $alias;

    public function as(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function toSql(SqlMode $sqlMode = SqlMode::Prepared): array
    {
        [$dml, $arguments] = parent::toSql($sqlMode);

        if (isset($this->alias)) {
            $alias = Wrapper::column($this->driver, $this->alias);

            return ["({$dml}) AS {$alias}", $arguments];
        }

        return ["({$dml})", $arguments];
    }

    public static function make(): self
    {
        return new self();
    }
}

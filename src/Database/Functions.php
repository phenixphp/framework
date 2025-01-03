<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Constants\DatabaseFunction;
use Stringable;

class Functions implements Stringable
{
    protected string $alias;

    public function __construct(
        protected readonly DatabaseFunction $function,
        protected readonly string $column
    ) {
        // ..
    }

    public function as(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function __toString(): string
    {
        $function = $this->function->name . '(' . $this->column . ')';

        if (isset($this->alias)) {
            $function .= ' AS ' . $this->alias;
        }

        return $function;
    }

    public static function avg(string $column): self
    {
        return new self(DatabaseFunction::AVG, $column);
    }

    public static function sum(string $column): self
    {
        return new self(DatabaseFunction::SUM, $column);
    }

    public static function min(string $column): self
    {
        return new self(DatabaseFunction::MIN, $column);
    }

    public static function max(string $column): self
    {
        return new self(DatabaseFunction::MAX, $column);
    }

    public static function count(string $column): self
    {
        return new self(DatabaseFunction::COUNT, $column);
    }

    public static function date(string $column): self
    {
        return new self(DatabaseFunction::DATE, $column);
    }

    public static function month(string $column): self
    {
        return new self(DatabaseFunction::MONTH, $column);
    }

    public static function year(string $column): self
    {
        return new self(DatabaseFunction::YEAR, $column);
    }

    public static function case(): SelectCase
    {
        return new SelectCase();
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Concerns\HasDriver;
use Phenix\Database\Constants\DatabaseFunction;
use Stringable;

class Funct implements Stringable
{
    use HasDriver;

    protected string $alias;

    public function __construct(
        protected readonly DatabaseFunction $function,
        protected readonly string $column
    ) {
        // ..
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

    public function as(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function __toString(): string
    {
        $column = Wrapper::column($this->driver, $this->column);

        $function = $this->function->name . '(' . $column . ')';

        if (isset($this->alias)) {
            $function .= ' AS ' . Wrapper::column($this->driver, $this->alias);
        }

        return $function;
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Concerns\HasDriver;
use Stringable;

class Alias implements Stringable
{
    use HasDriver;

    protected string $alias;

    public function __construct(protected readonly string $name)
    {
        // ..
    }

    public function as(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function __toString(): string
    {
        $parts = array_map(
            fn (string $part): string => (string) Wrapper::of($this->getDriver(), $part),
            explode('.', $this->name)
        );

        $alias = Wrapper::of($this->getDriver(), $this->alias);

        return implode('.', $parts) . " AS {$alias}";
    }

    public static function of(string $name): self
    {
        return new self($name);
    }
}

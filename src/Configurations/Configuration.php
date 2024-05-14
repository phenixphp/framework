<?php

declare(strict_types=1);

namespace Phenix\Configurations;

use Phenix\Contracts\Arrayable;
use Phenix\Util\Str;

abstract class Configuration implements Arrayable
{
    abstract public static function build(): static;

    public function toArray(): array
    {
        $data = [];

        foreach ($this as $key => $value) {
            $data[Str::snake($key)] = $value;
        }

        return $data;
    }
}

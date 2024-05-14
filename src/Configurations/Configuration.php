<?php

declare(strict_types=1);

namespace Phenix\Configurations;

use Phenix\Contracts\Arrayable;
use Phenix\Contracts\Buildable;

abstract class Configuration implements Arrayable, Buildable
{
    abstract public static function build(): self;
}

<?php

declare(strict_types=1);

namespace Phenix\Validation\Contracts;

use Phenix\Contracts\Arrayable;

interface Type extends Arrayable
{
    public function isRequired(): bool;
}

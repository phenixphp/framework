<?php

declare(strict_types=1);

namespace Phenix\Validation\Contracts;

use Phenix\Validation\Rules\TypeRule;

interface DefineArr
{
    public function define(TypeRule|array $definition): static;
}

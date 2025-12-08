<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Phenix\Http\Contracts\HeaderBuilder as HeaderBuilderContract;

abstract class HeaderBuilder implements HeaderBuilderContract
{
    abstract protected function value(): string;
}

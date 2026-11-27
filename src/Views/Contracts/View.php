<?php

declare(strict_types=1);

namespace Phenix\Views\Contracts;

use Stringable;

interface View extends Stringable
{
    public function render(): string;
}

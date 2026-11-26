<?php

declare(strict_types=1);

namespace Phenix\Views\Contracts;

use Amp\Cancellation;
use Stringable;

interface View extends Stringable
{
    public function render(Cancellation|null $cancellation = null): string;
}

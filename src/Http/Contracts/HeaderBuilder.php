<?php

declare(strict_types=1);

namespace Phenix\Http\Contracts;

use Amp\Http\Server\Response;

interface HeaderBuilder
{
    public function apply(Response $response): void;
}

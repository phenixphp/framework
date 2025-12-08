<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Amp\Http\Server\Response;

class CrossOriginResourcePolicy extends HeaderBuilder
{
    public function apply(Response $response): void
    {
        $response->setHeader('Cross-Origin-Resource-Policy', $this->value());
    }

    protected function value(): string
    {
        return 'same-origin';
    }
}

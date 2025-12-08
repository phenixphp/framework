<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Amp\Http\Server\Response;

class StrictTransportSecurity extends HeaderBuilder
{
    public function apply(Response $response): void
    {
        $response->setHeader('Strict-Transport-Security', $this->value());
    }

    protected function value(): string
    {
        return 'max-age=31536000; includeSubDomains; preload';
    }
}

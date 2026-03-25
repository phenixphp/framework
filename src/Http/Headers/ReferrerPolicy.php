<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Amp\Http\Server\Response;

class ReferrerPolicy extends HeaderBuilder
{
    public function apply(Response $response): void
    {
        $response->setHeader('Referrer-Policy', $this->value());
    }

    protected function value(): string
    {
        return 'no-referrer';
    }
}

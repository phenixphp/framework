<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Amp\Http\Server\Response;

class XDnsPrefetchControl extends HeaderBuilder
{
    public function apply(Response $response): void
    {
        $response->setHeader('X-DNS-Prefetch-Control', $this->value());
    }

    protected function value(): string
    {
        return 'off';
    }
}

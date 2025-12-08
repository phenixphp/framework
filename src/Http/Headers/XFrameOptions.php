<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Amp\Http\Server\Response;

class XFrameOptions extends HeaderBuilder
{
    public function apply(Response $response): void
    {
        $response->setHeader('X-Frame-Options', $this->value());
    }

    protected function value(): string
    {
        return 'SAMEORIGIN';
    }
}

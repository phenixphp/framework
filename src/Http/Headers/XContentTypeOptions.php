<?php

declare(strict_types=1);

namespace Phenix\Http\Headers;

use Amp\Http\Server\Response;

class XContentTypeOptions extends HeaderBuilder
{
    public function apply(Response $response): void
    {
        $response->setHeader('X-Content-Type-Options', $this->value());
    }

    protected function value(): string
    {
        return 'nosniff';
    }
}

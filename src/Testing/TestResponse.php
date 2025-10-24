<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Amp\Http\Client\Response;
use Phenix\Testing\Concerns\InteractWithHeaders;
use Phenix\Testing\Concerns\InteractWithJson;
use Phenix\Testing\Concerns\InteractWithStatusCode;
use PHPUnit\Framework\Assert;

class TestResponse
{
    use InteractWithJson;
    use InteractWithHeaders;
    use InteractWithStatusCode;

    public readonly string $body;

    public function __construct(public Response $response)
    {
        $this->body = $response->getBody()->buffer();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param array<int, string>|string $needles
     * @return self
     */
    public function assertBodyContains(array|string $needles): self
    {
        $needles = (array) $needles;

        foreach ($needles as $needle) {
            Assert::assertStringContainsString($needle, $this->body);
        }

        return $this;
    }
}

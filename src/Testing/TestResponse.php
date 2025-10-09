<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Amp\Http\Client\Response;
use Phenix\Http\Constants\HttpStatus;
use PHPUnit\Framework\Assert;

class TestResponse
{
    public readonly string $body;

    public function __construct(public Response $response)
    {
        $this->body = $response->getBody()->buffer();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getHeader(string $name): string|null
    {
        return $this->response->getHeader($name);
    }

    public function assertOk(): self
    {
        Assert::assertEquals(HttpStatus::OK->value, $this->response->getStatus());

        return $this;
    }

    public function assertNotFound(): self
    {
        Assert::assertEquals(HttpStatus::NOT_FOUND->value, $this->response->getStatus());

        return $this;
    }

    public function assertNotAcceptable(): self
    {
        Assert::assertEquals(HttpStatus::NOT_ACCEPTABLE->value, $this->response->getStatus());

        return $this;
    }

    public function assertUnprocessableEntity(): self
    {
        Assert::assertEquals(HttpStatus::UNPROCESSABLE_ENTITY->value, $this->response->getStatus());

        return $this;
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

    public function assertHeaderContains(array $needles): self
    {
        $needles = (array) $needles;

        foreach ($needles as $header => $value) {
            Assert::assertNotNull($this->response->getHeader($header));
            Assert::assertEquals($value, $this->response->getHeader($header));
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use PHPUnit\Framework\Assert;

trait InteractWithHeaders
{
    protected string $missingHeaderMessage = 'Response does not have a Content-Type header.';

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getHeader(string $name): string|null
    {
        return $this->response->getHeader($name);
    }

    public function assertHeaders(array $needles): self
    {
        foreach ($needles as $header => $value) {
            Assert::assertNotNull($this->response->getHeader($header));
            Assert::assertEquals($value, $this->response->getHeader($header));
        }

        return $this;
    }

    public function assertHeaderIsMissing(string $name): self
    {
        Assert::assertNull($this->response->getHeader($name));

        return $this;
    }

    public function assertIsJson(): self
    {
        $contentType = $this->response->getHeader('content-type');

        Assert::assertNotNull($contentType, $this->missingHeaderMessage);
        Assert::assertStringContainsString(
            'application/json',
            $contentType,
            'Response does not have a JSON content type.'
        );

        return $this;
    }

    public function assertIsHtml(): self
    {
        $contentType = $this->response->getHeader('content-type');

        Assert::assertNotNull($contentType, $this->missingHeaderMessage);
        Assert::assertStringContainsString(
            'text/html',
            $contentType,
            'Response does not have an HTML content type.'
        );

        return $this;
    }

    public function assertIsPlainText(): self
    {
        $contentType = $this->response->getHeader('content-type');

        Assert::assertNotNull($contentType, $this->missingHeaderMessage);
        Assert::assertStringContainsString(
            'text/plain',
            $contentType,
            'Response does not have a plain text content type.'
        );

        return $this;
    }
}

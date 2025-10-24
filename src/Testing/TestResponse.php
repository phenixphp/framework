<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Amp\Http\Client\Response;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Util\Arr;
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

    public function getDecodedBody(): array
    {
        $json = json_decode($this->body, true);

        Assert::assertNotNull($json, 'Response body is not valid JSON.');
        Assert::assertIsArray($json, 'Response JSON is not an array.');

        return $json;
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getHeader(string $name): string|null
    {
        return $this->response->getHeader($name);
    }

    public function assertStatusCode(HttpStatus $code): self
    {
        Assert::assertEquals($code->value, $this->response->getStatus());

        return $this;
    }

    public function assertOk(): self
    {
        Assert::assertEquals(HttpStatus::OK->value, $this->response->getStatus());

        return $this;
    }

    public function assertCreated(): self
    {
        Assert::assertEquals(HttpStatus::CREATED->value, $this->response->getStatus());

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

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public function assertJsonContains(array $data): self
    {
        $json = $this->getDecodedBody();

        foreach ($data as $key => $value) {
            Assert::assertArrayHasKey($key, $json);
            Assert::assertEquals($value, $json[$key]);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public function assertJsonDoesNotContain(array $data): self
    {
        $json = $this->getDecodedBody();

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $json)) {
                Assert::assertNotEquals($value, $json[$key]);
            }
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $fragment
     * @return self
     */
    public function assertJsonFragment(array $fragment): self
    {
        $json = $this->getDecodedBody();

        Assert::assertTrue(
            $this->hasFragment($json, $fragment),
            'Unable to find JSON fragment in response.'
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $fragment
     * @return self
     */
    public function assertJsonMissingFragment(array $fragment): self
    {
        $json = $this->getDecodedBody();

        Assert::assertFalse(
            $this->hasFragment($json, $fragment),
            'Found unexpected JSON fragment in response.'
        );

        return $this;
    }

    /**
     * @param string $path
     * @param mixed $expectedValue
     * @return self
     */
    public function assertJsonPath(string $path, mixed $expectedValue): self
    {
        $json = $this->getDecodedBody();

        Assert::assertTrue(
            Arr::has($json, $path),
            "Path '{$path}' does not exist in JSON response."
        );

        $value = Arr::get($json, $path);

        Assert::assertEquals(
            $expectedValue,
            $value,
            "Failed asserting that JSON path '{$path}' equals expected value."
        );

        return $this;
    }

    /**
     * @param string $path
     * @param mixed $expectedValue
     * @return self
     */
    public function assertJsonPathNotEquals(string $path, mixed $expectedValue): self
    {
        $json = $this->getDecodedBody();

        Assert::assertTrue(
            Arr::has($json, $path),
            "Path '{$path}' does not exist in JSON response."
        );

        $value = Arr::get($json, $path);

        Assert::assertNotEquals(
            $expectedValue,
            $value,
            "Failed asserting that JSON path '{$path}' does not equal the given value."
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $structure
     * @return self
     */
    public function assertJsonStructure(array $structure): self
    {
        $json = $this->getDecodedBody();

        $this->assertStructure($structure, $json);

        return $this;
    }

    /**
     * @return self
     */
    public function assertIsJson(): self
    {
        $contentType = $this->response->getHeader('content-type');

        Assert::assertNotNull($contentType, 'Response does not have a Content-Type header.');
        Assert::assertStringContainsString(
            'application/json',
            $contentType,
            'Response does not have a JSON content type.'
        );

        return $this;
    }

    /**
     * @param int $count
     * @return self
     */
    public function assertJsonCount(int $count): self
    {
        $json = $this->getDecodedBody();

        Assert::assertIsArray($json, 'Response JSON is not an array.');
        Assert::assertCount($count, $json, "Expected JSON array to have {$count} items.");

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $fragment
     * @return bool
     */
    protected function hasFragment(array $data, array $fragment): bool
    {
        // Check if fragment matches at the current level
        $matches = true;
        foreach ($fragment as $key => $value) {
            if (! array_key_exists($key, $data) || $data[$key] !== $value) {
                $matches = false;

                break;
            }
        }

        if ($matches) {
            return true;
        }

        // Recursively check nested arrays
        foreach ($data as $value) {
            if (is_array($value) && $this->hasFragment($value, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, mixed> $data
     * @param string $path
     * @return void
     */
    protected function assertStructure(array $structure, array $data, string $path = ''): void
    {
        foreach ($structure as $key => $value) {
            $currentPath = $path ? "{$path}.{$key}" : (string) $key;

            if (is_array($value)) {
                if ($key === '*') {
                    Assert::assertIsArray(
                        $data,
                        "Expected array at path '{$path}' but got " . gettype($data)
                    );

                    foreach ($data as $index => $item) {
                        $itemPath = $path ? "{$path}.{$index}" : (string) $index;
                        Assert::assertIsArray(
                            $item,
                            "Expected array at path '{$itemPath}' but got " . gettype($item)
                        );
                        $this->assertStructure($value, $item, $itemPath);
                    }
                } else {
                    Assert::assertArrayHasKey(
                        $key,
                        $data,
                        "Missing key '{$key}' at path '{$currentPath}'"
                    );
                    Assert::assertIsArray(
                        $data[$key],
                        "Expected array at path '{$currentPath}' but got " . gettype($data[$key])
                    );
                    $this->assertStructure($value, $data[$key], $currentPath);
                }
            } else {
                Assert::assertArrayHasKey(
                    $value,
                    $data,
                    "Missing key '{$value}' at path '{$currentPath}'"
                );
            }
        }
    }
}

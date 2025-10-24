<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Phenix\Util\Arr;
use PHPUnit\Framework\Assert;

trait InteractWithJson
{
    public function getDecodedBody(): array
    {
        $json = json_decode($this->body, true);

        Assert::assertNotNull($json, 'Response body is not valid JSON.');
        Assert::assertIsArray($json, 'Response JSON is not an array.');

        return $json;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public function assertJsonContains(array $data, string|null $path = null): self
    {
        $json = $this->getDecodedBody();

        if ($path) {
            Assert::assertArrayHasKey(
                $path,
                $json,
                "Response JSON does not have the expected '{$path}' wrapper."
            );

            $json = Arr::get($json, $path, []);
        }

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
    public function assertJsonDoesNotContain(array $data, string|null $path = null): self
    {
        $json = $this->getDecodedBody();

        if ($path) {
            Assert::assertArrayHasKey(
                $path,
                $json,
                "Response JSON does not have the expected '{$path}' wrapper."
            );

            $json = Arr::get($json, $path, []);
        }

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
     * @param int $count
     * @param string|null $path
     * @return self
     */
    public function assertJsonCount(int $count, string|null $path = null): self
    {
        $json = $this->getDecodedBody();

        if ($path) {
            Assert::assertArrayHasKey(
                $path,
                $json,
                "Path '{$path}' does not exist in JSON response."
            );

            $json = Arr::get($json, $path);
        }

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

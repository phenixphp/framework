<?php

declare(strict_types=1);

namespace Phenix\Http\Contracts;

use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\Request;
use Phenix\Contracts\Arrayable;

interface BodyParser extends Arrayable
{
    public static function fromRequest(Request $request, array $options = []): self;

    public function has(string $key): bool;

    public function get(string $key, array|string|int|null $default = null): BufferedFile|array|string|int|null;

    public function integer(string $key): int|null;

    public function hasFile(string $key): bool;

    public function getFile(string $key, array|string|int|null $default = null): BufferedFile|string|null;

    public function files(): array;
}

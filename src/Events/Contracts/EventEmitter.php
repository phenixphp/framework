<?php

declare(strict_types=1);

namespace Phenix\Events\Contracts;

use Amp\Future;
use Closure;

interface EventEmitter
{
    public function on(string $event, Closure|EventListener|string $listener, int $priority = 0): void;

    public function once(string $event, Closure|EventListener|string $listener, int $priority = 0): void;

    public function off(string $event, Closure|EventListener|string|null $listener = null): void;

    public function emit(string|Event $event, mixed $payload = null): array;

    public function emitAsync(string|Event $event, mixed $payload = null): Future;

    public function getListeners(string $event): array;

    public function hasListeners(string $event): bool;

    public function removeAllListeners(): void;
}

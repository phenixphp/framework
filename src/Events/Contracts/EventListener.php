<?php

declare(strict_types=1);

namespace Phenix\Events\Contracts;

use Closure;

interface EventListener
{
    public function handle(Event $event): mixed;

    public function getPriority(): int;

    public function shouldHandle(Event $event): bool;

    public function isOnce(): bool;

    public function setOnce(bool $once = true): self;

    public function getHandler(): Closure|static|string;
}

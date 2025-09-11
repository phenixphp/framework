<?php

declare(strict_types=1);

namespace Phenix\Events\Contracts;

interface EventListener
{
    public function handle(Event $event): mixed;

    public function getPriority(): int;

    public function shouldHandle(Event $event): bool;
}

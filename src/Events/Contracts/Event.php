<?php

declare(strict_types=1);

namespace Phenix\Events\Contracts;

interface Event
{
    public function getName(): string;

    public function getPayload(): mixed;

    public function isPropagationStopped(): bool;

    public function stopPropagation(): void;
}

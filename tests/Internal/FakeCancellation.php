<?php

declare(strict_types=1);

namespace Tests\Internal;

use Amp\Cancellation;
use Closure;

class FakeCancellation implements Cancellation
{
    public function subscribe(Closure $callback): string
    {
        return 'id';
    }

    public function unsubscribe(string $id): void
    {
        // This method is intentionally left empty because the cancellation logic is not required in this test context.
    }

    public function isRequested(): bool
    {
        return true;
    }

    public function throwIfRequested(): void
    {
        // This method is intentionally left empty in the test context.
        // However, in a real implementation, this would throw an exception
        // to indicate that the cancellation has been requested.
    }
}
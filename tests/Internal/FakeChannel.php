<?php

declare(strict_types=1);

namespace Tests\Internal;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Closure;

class FakeChannel implements Channel
{
    public function receive(Cancellation|null $cancellation = null): mixed
    {
        return true;
    }

    public function send(mixed $data): void
    {
        // This method is intentionally left empty because the test context does not require
        // sending data through the channel. In a production environment, this method should
        // be implemented to handle the transmission of data to the intended recipient.
    }

    public function close(): void
    {
        // This method is intentionally left empty because the test context does not require
        // any specific actions to be performed when the channel is closed. In a production
        // environment, this method should be implemented to release resources or perform
        // necessary cleanup operations.
    }

    public function isClosed(): bool
    {
        return false;
    }

    public function onClose(Closure $onClose): void
    {
        // This method is intentionally left empty because the test context does not require
        // handling of the onClose callback. If used in production, this should be implemented
        // to handle resource cleanup or other necessary actions when the channel is closed.
    }
}

<?php

declare(strict_types=1);

namespace Tests;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Closure;
use Phenix\Testing\TestCase as TestingTestCase;

class TestCase extends TestingTestCase
{
    protected function getAppDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'application';
    }

    protected function getFakeChannel(): Channel
    {
        return new class () implements Channel {
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
        };
    }

    protected function getFakeCancellation(): Cancellation
    {
        return new class () implements Cancellation {
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
        };
    }
}

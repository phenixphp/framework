<?php

declare(strict_types=1);

namespace Tests;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Testing\TestCase as BaseTestCase;
use Tests\Internal\FakeCancellation;
use Tests\Internal\FakeChannel;

class TestCase extends BaseTestCase
{
    protected function getAppDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'application';
    }

    protected function getFakeChannel(): Channel
    {
        return new FakeChannel();
    }

    protected function getFakeCancellation(): Cancellation
    {
        return new FakeCancellation();
    }
}

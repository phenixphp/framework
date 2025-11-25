<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Amp\PHPUnit\AsyncTestCase;
use Phenix\App;
use Phenix\AppBuilder;
use Phenix\AppProxy;
use Phenix\Cache\Constants\Store;
use Phenix\Console\Phenix;
use Phenix\Facades\Cache;
use Phenix\Facades\Event;
use Phenix\Facades\Mail;
use Phenix\Facades\Queue;
use Phenix\Facades\View;
use Phenix\Testing\Concerns\InteractWithDatabase;
use Phenix\Testing\Concerns\InteractWithResponses;
use Phenix\Testing\Concerns\RefreshDatabase;
use Symfony\Component\Console\Tester\CommandTester;

use function in_array;

abstract class TestCase extends AsyncTestCase
{
    use InteractWithResponses;
    use InteractWithDatabase;

    protected ?AppProxy $app;
    protected string $appDir;

    abstract protected function getAppDir(): string;

    protected function setUp(): void
    {
        parent::setUp();

        if (! isset($this->app)) {
            $this->app = AppBuilder::build($this->getAppDir(), $this->getEnvFile());
            $this->app->enableTestingMode();
        }

        $uses = class_uses_recursive($this);

        if (in_array(RefreshDatabase::class, $uses, true) && method_exists($this, 'refreshDatabase')) {
            $this->refreshDatabase();
        }

        View::clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Event::resetFaking();
        Queue::resetFaking();
        Mail::resetSendingLog();

        if (config('cache.default') !== Store::REDIS->value) {
            Cache::clear();
        }

        $this->app = null;
    }

    protected function phenix(string $signature, array $arguments = [], array $inputs = []): CommandTester
    {
        $phenix = App::make(Phenix::class);

        $command = $phenix->find($signature);
        $commandTester = new CommandTester($command);

        if (! empty($inputs)) {
            $commandTester->setInputs($inputs);
        }

        $commandTester->execute($arguments);

        return $commandTester;
    }

    protected function getEnvFile(): string|null
    {
        return null;
    }
}

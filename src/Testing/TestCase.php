<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Amp\PHPUnit\AsyncTestCase;
use Phenix\App;
use Phenix\AppBuilder;
use Phenix\AppProxy;
use Phenix\Console\Phenix;
use Phenix\Testing\Concerns\InteractWithResponses;
use Symfony\Component\Console\Tester\CommandTester;

abstract class TestCase extends AsyncTestCase
{
    use InteractWithResponses;

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();

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

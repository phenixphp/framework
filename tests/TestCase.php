<?php

declare(strict_types=1);

namespace Tests;

use Amp\PHPUnit\AsyncTestCase;
use Phenix\App;
use Phenix\AppBuilder;
use Phenix\AppProxy;
use Phenix\Console\Phenix;
use Symfony\Component\Console\Tester\CommandTester;

class TestCase extends AsyncTestCase
{
    protected ?AppProxy $app;
    protected string $appDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! isset($this->app)) {
            $this->prepareAppDir();

            $this->app = AppBuilder::build($this->appDir);
            $this->app->enableTestingMode();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->app = null;
    }

    protected function phenix(string $signature, array $arguments): CommandTester
    {
        $phenix = App::make(Phenix::class);

        $command = $phenix->find($signature);
        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);

        return $commandTester;
    }

    private function prepareAppDir(): void
    {
        $this->appDir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'application';
    }
}

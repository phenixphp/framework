<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Cancellation;
use Amp\CompositeCancellation;
use Amp\Sync\Channel;
use Amp\TimeoutCancellation;
use Phenix\AppBuilder;
use Phenix\AppProxy;
use Phenix\Facades\Config;
use Phenix\Tasks\Contracts\Task as TaskContract;
use Phenix\Tasks\Exceptions\BootstrapAppException;

abstract class Task implements TaskContract
{
    protected int $timeout = 60;

    abstract protected function handle(Channel $channel, Cancellation $cancellation): mixed;

    public static function setBootingSettings(): void
    {
        if (getenv('PHENIX_BASE_PATH') === false) {
            putenv('PHENIX_BASE_PATH=' . base_path());
            $_ENV['PHENIX_BASE_PATH'] = base_path();
        }
    }

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $app = self::bootApplication();

        if (Config::get('app.debug')) {
            $app->enableTestingMode();
        }

        $timeout = new TimeoutCancellation($this->getTimeout());
        $combined = new CompositeCancellation($cancellation, $timeout);

        $combined->throwIfRequested();

        return $this->handle($channel, $cancellation);
    }

    public function output(): Result
    {
        /** @var Result $result */
        [$result] = Worker::batch([
            $this,
        ]);

        return $result;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    protected static function bootApplication(): AppProxy
    {
        $basePath = $_ENV['PHENIX_BASE_PATH'] ?? getenv('PHENIX_BASE_PATH');

        if (! $basePath) {
            throw new BootstrapAppException('App base path is not set, called by ' . static::class);
        }

        return AppBuilder::build($basePath);
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Phenix\AppProxy;
use Amp\Cancellation;
use Amp\Sync\Channel;
use RuntimeException;
use Phenix\AppBuilder;
use Phenix\Facades\Config;
use Phenix\Tasks\Contracts\Task as TaskContract;

abstract class Task implements TaskContract
{
    abstract protected function handle(Channel $channel, Cancellation $cancellation): mixed;

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $app = self::bootApplication();

        if (Config::get('app.debug')) {
            $app->enableTestingMode();
        }

        return $this->handle($channel, $cancellation);
    }

    public static function setBootingSettings(): void
    {
        if (getenv('PHENIX_BASE_PATH') === false) {
            putenv('PHENIX_BASE_PATH=' . base_path());
            $_ENV['PHENIX_BASE_PATH'] = base_path();
        }
    }

    protected static function bootApplication(): AppProxy
    {
        $basePath = $_ENV['PHENIX_BASE_PATH'] ?? getenv('PHENIX_BASE_PATH');

        if (!$basePath) {
            throw new RuntimeException('App base path is not set, called by ' . static::class);
        }

        return AppBuilder::build($basePath);
    }
}

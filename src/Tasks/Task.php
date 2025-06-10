<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\AppBuilder;
use Phenix\AppProxy;
use Phenix\Facades\Config;
use Phenix\Tasks\Contracts\Task as TaskContract;

abstract class Task implements TaskContract
{
    protected static string $basePath;

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
        self::setBasePath();
    }

    protected static function setBasePath(): void
    {
        if (!isset(static::$basePath)) {
            static::$basePath = base_path();
        }
    }

    protected static function bootApplication(): AppProxy
    {
        return AppBuilder::build(static::$basePath);
    }
}

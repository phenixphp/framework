<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\AppBuilder;
use Phenix\Facades\Config;
use Phenix\Tasks\Contracts\ParallelTask as ParallelTaskContract;

abstract class AppParallelTask implements ParallelTaskContract
{
    protected string $basePath;

    protected bool $isDebugging;

    public function __construct()
    {
        $this->basePath = base_path();
        $this->isDebugging = Config::get('app.debug');
    }

    abstract protected function handle(Channel $channel, Cancellation $cancellation): mixed;

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $app = AppBuilder::build($this->basePath);

        if ($this->isDebugging) {
            $app->enableTestingMode();
        }

        return $this->handle($channel, $cancellation);
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Queue\Constants\QueueDriver;
use Phenix\Facades\Config as Configuration;

class Config
{
    private array $config;

    public function __construct()
    {
        $this->config = Configuration::get('queue', []);
    }

    public function default(): string
    {
        return $this->config['default'] ?? QueueDriver::PARALLEL->value;
    }

    public function getDriver(string|null $driverName = null): array
    {
        $driverName ??= $this->default();

        return $this->config['drivers'][$driverName] ?? [];
    }
}

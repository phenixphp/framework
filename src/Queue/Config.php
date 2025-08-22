<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Facades\Config as Configuration;
use Phenix\Queue\Constants\QueueDriver;

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

    public function getConnection(): string
    {
        return $this->getDriver()['connection'] ?? 'default';
    }
}

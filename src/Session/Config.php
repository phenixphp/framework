<?php

declare(strict_types=1);

namespace Phenix\Session;

use Phenix\Contracts\Arrayable;
use Phenix\Facades\Config as Configuration;
use Phenix\Session\Constants\Driver;
use Phenix\Session\Constants\SameSite;

class Config implements Arrayable
{
    private array $config;

    public function __construct()
    {
        $this->config = Configuration::get('session', []);
    }

    public function driver(): Driver
    {
        return Driver::tryFrom($this->config['driver']) ?? Driver::LOCAL;
    }

    public function lifetime(): int
    {
        $lifetime = $this->config['lifetime'] ?? 120;

        return (int) $lifetime;
    }

    public function connection(): string
    {
        return $this->config['connection'] ?? 'default';
    }

    public function cookieName(): string
    {
        return $this->config['cookie_name'] ?? 'phenix_session';
    }

    public function path(): string
    {
        return $this->config['path'] ?? '/';
    }

    public function domain(): string|null
    {
        return $this->config['domain'];
    }

    public function secure(): bool
    {
        return $this->config['secure'] ?? false;
    }

    public function httpOnly(): bool
    {
        return $this->config['http_only'] ?? true;
    }

    public function sameSite(): SameSite
    {
        return SameSite::from($this->config['same_site']);
    }

    public function toArray(): array
    {
        return $this->config;
    }
}

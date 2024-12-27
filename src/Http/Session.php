<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\Server\Session\Session as ServerSession;

class Session
{
    public function __construct(
        protected ServerSession $session
    ) {
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->session->get($name) ?? $default;
    }

    public function set(string $name, mixed $value): void
    {
        $this->session->set($name, $value);
    }

    public function put(string $name, mixed $value): void
    {
        $this->lock();
        $this->set($name, $value);
        $this->commit();
    }

    public function has(string $name): bool
    {
        return $this->session->has($name);
    }

    public function delete(string $name): void
    {
        $this->session->lock();
        $this->session->unset($name);
        $this->session->commit();
    }

    public function clear(): void
    {
        $this->session->destroy();
    }

    public function regenerate(): void
    {
        $this->session->regenerate();
    }

    public function getId(): ?string
    {
        return $this->session->getId();
    }

    public function isRead(): bool
    {
        return $this->session->isRead();
    }

    public function isLocked(): bool
    {
        return $this->session->isLocked();
    }

    public function isEmpty(): bool
    {
        return $this->session->isEmpty();
    }

    public function lock(): void
    {
        $this->session->lock();
    }

    public function commit(): void
    {
        $this->session->commit();
    }

    public function rollback(): void
    {
        $this->session->rollback();
    }

    public function unlock(): void
    {
        $this->session->unlock();
    }

    public function unlockAll(): void
    {
        $this->session->unlockAll();
    }

    public function getData(): array
    {
        return $this->session->getData();
    }
}

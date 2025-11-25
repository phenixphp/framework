<?php

declare(strict_types=1);

namespace Phenix\Cache\Stores;

use Closure;
use Phenix\Cache\CacheStore;
use Phenix\Facades\File;
use Phenix\Util\Arr;
use Phenix\Util\Date;

use function is_array;

class FileStore extends CacheStore
{
    public function __construct(
        protected string $path,
        protected string $prefix = '',
        protected int $ttl = 60
    ) {
    }

    public function get(string $key, Closure|null $callback = null): mixed
    {
        $filename = $this->filename($key);

        if (! File::isFile($filename) || ! $raw = File::get($filename)) {
            return $this->resolveCallback($key, $callback);
        }

        $data = json_decode($raw, true);

        if (! is_array($data) || ! Arr::has($data, ['expires_at', 'value'])) {
            $this->delete($key);

            return $this->resolveCallback($key, $callback);
        }

        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);

            $value = $this->resolveCallback($key, $callback);
        } else {
            $value = unserialize(base64_decode($data['value']));
        }

        return $value;
    }

    public function set(string $key, mixed $value, Date|null $ttl = null): void
    {
        $ttl ??= Date::now()->addMinutes($this->ttl);
        $expiresAt = $ttl->getTimestamp();

        $payload = [
            'expires_at' => $expiresAt,
            'value' => base64_encode(serialize($value)),
        ];

        File::put($this->filename($key), json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function forever(string $key, mixed $value): void
    {
        $payload = [
            'expires_at' => null,
            'value' => base64_encode(serialize($value)),
        ];

        File::put($this->filename($key), json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function has(string $key): bool
    {
        $filename = $this->filename($key);

        if (! File::isFile($filename) || ! $raw = File::get($filename)) {
            return false;
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            return false;
        }

        $has = true;

        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);

            $has = false;
        }

        return $has;
    }

    public function delete(string $key): void
    {
        $filename = $this->filename($key);

        if (File::isFile($filename)) {
            File::deleteFile($filename);
        }
    }

    public function clear(): void
    {
        if (! File::isDirectory($this->path)) {
            return;
        }

        $files = File::listFiles($this->path, false);

        foreach ($files as $file) {
            if (str_ends_with($file, '.cache')) {
                File::deleteFile($file);
            }
        }
    }

    protected function filename(string $key): string
    {
        return $this->path . DIRECTORY_SEPARATOR . sha1($this->prefix . $key) . '.cache';
    }

    protected function resolveCallback(string $key, Closure|null $callback): mixed
    {
        if ($callback === null) {
            return null;
        }

        $value = $callback();

        $this->set($key, $value);

        return $value;
    }
}

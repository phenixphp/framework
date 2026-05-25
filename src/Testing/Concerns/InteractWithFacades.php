<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Closure;
use Phenix\App;
use Phenix\Cache\CacheManager;
use Phenix\Cache\Constants\Store;
use Phenix\Events\EventEmitter;
use Phenix\Facades\Cache;
use Phenix\Facades\Event;
use Phenix\Facades\Http;
use Phenix\Facades\Mail;
use Phenix\Facades\Queue;
use Phenix\Facades\View;
use Phenix\Http\Client\HttpClient;
use Phenix\Mail\MailManager;
use Phenix\Queue\QueueManager;
use Phenix\Views\Contracts\TemplateEngine;

trait InteractWithFacades
{
    protected function clearViewCacheIfAvailable(): void
    {
        $this->whenBound(TemplateEngine::class, static function (): void {
            View::clearCache();
        });
    }

    protected function resetEventsIfAvailable(): void
    {
        $this->whenBound(EventEmitter::class, static function (): void {
            Event::resetFaking();
        });
    }

    protected function resetQueueIfAvailable(): void
    {
        $this->whenBound(QueueManager::class, static function (): void {
            Queue::resetFaking();
        });
    }

    protected function resetMailIfAvailable(): void
    {
        $this->whenBound(MailManager::class, static function (): void {
            Mail::resetSendingLog();
        });
    }

    protected function resetHttpIfAvailable(): void
    {
        $this->whenBound(HttpClient::class, static function (): void {
            Http::resetFaking();
        });
    }

    protected function clearCacheIfAvailable(): void
    {
        if (config('cache.default') !== Store::FILE->value) {
            return;
        }

        $this->whenBound(CacheManager::class, static function (): void {
            Cache::clear();
        });
    }

    protected function whenBound(string $key, Closure $callback): void
    {
        if (App::has($key)) {
            $callback();
        }
    }
}

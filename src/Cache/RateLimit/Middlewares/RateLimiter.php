<?php

declare(strict_types=1);

namespace Phenix\Cache\RateLimit\Middlewares;

use Phenix\App;
use Phenix\Auth\User;
use Phenix\Http\Session;
use Phenix\Facades\Config;
use Phenix\Http\IpAddress;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Cache\RateLimit\RateLimitManager;
use Amp\Http\Server\Session\Session as ServerSession;

class RateLimiter implements Middleware
{
    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        $config = Config::get('cache.rate_limit', []);

        if (!Config::get('cache.rate_limit.enabled', false)) {
            return $next->handleRequest($request);
        }

        /** @var RateLimitManager $rateLimiter */
        $rateLimiter = App::make(RateLimitManager::class);

        $key = $this->resolveKey($request) ?? 'guest';
        $current = $rateLimiter->increment($key);

        if ($current > Config::get('cache.rate_limit.per_minute', 60)) {
            return $this->createRateLimitExceededResponse($rateLimiter, $key);
        }

        $response = $next->handleRequest($request);

        return $this->addRateLimitHeaders($rateLimiter, $request, $response, $current, $key);
    }

    protected function resolveKey(Request $request): string|null
    {
        $user = $this->user($request);

        if ($user) {
            return (string) $user->getKey();
        }

        $ip = IpAddress::parse($request);

        return $ip !== null ? $ip : $this->getSessionId($request);
    }

    protected function user(Request $request): User|null
    {
        $key = Config::get('auth.users.model', User::class);

        return $request->hasAttribute($key) ? $request->getAttribute($key) : null;
    }

    protected function getSessionId(Request $request): string|null
    {
        $session = null;

        if ($request->hasAttribute(ServerSession::class)) {
            $session = new Session($request->getAttribute(ServerSession::class));
        }

        return $session?->getId();
    }

    protected function createRateLimitExceededResponse(RateLimitManager $rateLimiter, string $key): Response
    {
        $retryAfter = $rateLimiter->getTtl($key);

        return new Response(
            status: HttpStatus::TOO_MANY_REQUESTS->value,
            headers: [
                'retry-after' => (string) $retryAfter,
                'content-type' => 'application/json',
            ],
            body: json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ])
        );
    }

    protected function addRateLimitHeaders(RateLimitManager $rateLimiter, Request $request, Response $response, int $current, string $key): Response
    {
        $remaining = max(0, Config::get('cache.rate_limit.per_minute', 60) - $current);
        $resetTime = time() + $rateLimiter->getTtl($key);

        if ($this->user($request)) {
            $response->addHeader('x-ratelimit-limit', (string) Config::get('cache.rate_limit.per_minute', 60));
            $response->addHeader('x-ratelimit-remaining', (string) $remaining);
            $response->addHeader('x-ratelimit-reset', (string) $resetTime);
            $response->addHeader('x-ratelimit-reset-after', (string) $rateLimiter->getTtl($key));
        }

        return $response;
    }
}

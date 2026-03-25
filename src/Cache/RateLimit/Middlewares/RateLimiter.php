<?php

declare(strict_types=1);

namespace Phenix\Cache\RateLimit\Middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Phenix\App;
use Phenix\Cache\RateLimit\RateLimitManager;
use Phenix\Facades\Config;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Ip;

class RateLimiter implements Middleware
{
    protected RateLimitManager $rateLimiter;

    protected int|null $perMinuteLimit;

    protected string $prefix;

    public function __construct(int|null $perMinuteLimit = null, string $prefix = 'global')
    {
        $this->rateLimiter = App::make(RateLimitManager::class);
        $this->perMinuteLimit = $perMinuteLimit;
        $this->prefix = $prefix;
    }

    public static function perMinute(int $maxAttempts, string $prefix = 'custom'): self
    {
        return new self($maxAttempts, $prefix);
    }

    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        $isCustom = $this->perMinuteLimit !== null;

        if (! $isCustom && ! Config::get('cache.rate_limit.enabled', false)) {
            return $next->handleRequest($request);
        }

        $clientIp = "{$this->prefix}:" . Ip::make($request)->hash();
        $current = $this->rateLimiter->increment($clientIp);

        $perMinuteLimit = $this->perMinuteLimit ?? (int) Config::get('cache.rate_limit.per_minute', 60);

        if ($current > $perMinuteLimit) {
            return $this->rateLimitExceededResponse($clientIp);
        }

        $response = $next->handleRequest($request);
        $remaining = max(0, $perMinuteLimit - $current);
        $resetTime = time() + $this->rateLimiter->getTtl($clientIp);

        $response->addHeader('x-ratelimit-limit', (string) $perMinuteLimit);
        $response->addHeader('x-ratelimit-remaining', (string) $remaining);
        $response->addHeader('x-ratelimit-reset', (string) $resetTime);
        $response->addHeader('x-ratelimit-reset-after', (string) $this->rateLimiter->getTtl($clientIp));

        return $response;
    }

    protected function rateLimitExceededResponse(string $identifier): Response
    {
        $retryAfter = $this->rateLimiter->getTtl($identifier);

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
}

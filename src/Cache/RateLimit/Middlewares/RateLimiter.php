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
use Phenix\Http\IpAddress;

class RateLimiter implements Middleware
{
    protected RateLimitManager $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = App::make(RateLimitManager::class);
    }

    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        if (! Config::get('cache.rate_limit.enabled', false)) {
            return $next->handleRequest($request);
        }

        $identifier = IpAddress::hash($request) ?? 'guest';
        $current = $this->rateLimiter->increment($identifier);

        $perMinuteLimit = Config::get('cache.rate_limit.per_minute', 60);

        if ($current > $perMinuteLimit) {
            return $this->rateLimitExceededResponse($identifier);
        }

        $response = $next->handleRequest($request);
        $remaining = max(0, $perMinuteLimit - $current);
        $resetTime = time() + $this->rateLimiter->getTtl($identifier);

        $response->addHeader('x-ratelimit-limit', (string) $perMinuteLimit);
        $response->addHeader('x-ratelimit-remaining', (string) $remaining);
        $response->addHeader('x-ratelimit-reset', (string) $resetTime);
        $response->addHeader('x-ratelimit-reset-after', (string) $this->rateLimiter->getTtl($identifier));

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

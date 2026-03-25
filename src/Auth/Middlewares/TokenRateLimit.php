<?php

declare(strict_types=1);

namespace Phenix\Auth\Middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Phenix\App;
use Phenix\Auth\AuthenticationManager;
use Phenix\Auth\Middlewares\Concerns\InteractsWithBearerTokens;
use Phenix\Facades\Config;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Ip;

class TokenRateLimit implements Middleware
{
    use InteractsWithBearerTokens;

    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        $authorizationHeader = $request->getHeader('Authorization');

        if (! $this->hasBearerScheme($authorizationHeader)) {
            return $next->handleRequest($request);
        }

        /** @var AuthenticationManager $auth */
        $auth = App::make(AuthenticationManager::class);

        $clientIp = Ip::make($request)->hash();

        $attemptLimit = (int) (Config::get('auth.tokens.rate_limit.attempts', 5));
        $windowSeconds = (int) (Config::get('auth.tokens.rate_limit.window', 300));

        if ($auth->getAttempts($clientIp) >= $attemptLimit) {
            return response()->json(
                content: ['error' => 'Too many token validation attempts'],
                status: HttpStatus::TOO_MANY_REQUESTS,
                headers: [
                    'Retry-After' => (string) $windowSeconds,
                ]
            )->send();
        }

        return $next->handleRequest($request);
    }
}

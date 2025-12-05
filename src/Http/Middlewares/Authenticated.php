<?php

declare(strict_types=1);

namespace Phenix\Http\Middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Phenix\App;
use Phenix\Auth\AuthenticationManager;
use Phenix\Auth\Events\FailedTokenValidation;
use Phenix\Auth\Events\TokenValidated;
use Phenix\Auth\User;
use Phenix\Facades\Config;
use Phenix\Facades\Event;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\IpAddress;
use Phenix\Http\Request as HttpRequest;

class Authenticated implements Middleware
{
    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        dump(__CLASS__ . ' invoked');

        $authorizationHeader = $request->getHeader('Authorization');

        if (! $this->hasToken($authorizationHeader)) {
            return $this->unauthorized();
        }

        $token = $this->extractToken($authorizationHeader);

        /** @var AuthenticationManager $auth */
        $auth = App::make(AuthenticationManager::class);

        $clientIdentifier = 'unknown';

        if ($ip = IpAddress::parse($request)) {
            $clientIdentifier = parse_url($ip, PHP_URL_HOST) ?? 'unknown';
        }

        if (! $token || ! $auth->validate($token)) {
            Event::emitAsync(new FailedTokenValidation(
                request: new HttpRequest($request),
                clientIp: $clientIdentifier,
                reason: $token ? 'validation_failed' : 'invalid_format',
                attemptedToken: $token,
                attemptCount: $auth->getAttempts($clientIdentifier)
            ));

            $auth->increaseAttempts($clientIdentifier);

            return $this->unauthorized();
        }

        Event::emitAsync(new TokenValidated(
            token: $auth->user()?->currentAccessToken(),
            request: new HttpRequest($request),
            clientIp: $clientIdentifier
        ));

        $auth->resetAttempts($clientIdentifier);

        $request->setAttribute(Config::get('auth.users.model', User::class), $auth->user());

        return $next->handleRequest($request);
    }

    protected function hasToken(string|null $token): bool
    {
        return $token !== null
            && trim($token) !== ''
            && str_starts_with($token, 'Bearer ');
    }

    protected function extractToken(string $authorizationHeader): string|null
    {
        $parts = explode(' ', $authorizationHeader, 2);

        return isset($parts[1]) ? trim($parts[1]) : null;
    }

    protected function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthorized',
        ], HttpStatus::UNAUTHORIZED)->send();
    }
}

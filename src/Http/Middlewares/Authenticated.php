<?php

declare(strict_types=1);

namespace Phenix\Http\Middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Phenix\App;
use Phenix\Auth\AuthenticationManager;
use Phenix\Auth\User;
use Phenix\Facades\Config;
use Phenix\Http\Constants\HttpStatus;

class Authenticated implements Middleware
{
    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        $authorizationHeader = $request->getHeader('Authorization');

        if (! $this->hasToken($authorizationHeader)) {
            return $this->unauthorized();
        }

        $token = $this->extractToken($authorizationHeader);

        /** @var AuthenticationManager $auth */
        $auth = App::make(AuthenticationManager::class);

        if (! $token || ! $auth->validate($token)) {
            return $this->unauthorized();
        }

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

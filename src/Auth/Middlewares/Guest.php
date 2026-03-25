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
use Phenix\Http\Constants\HttpStatus;

class Guest implements Middleware
{
    use InteractsWithBearerTokens;

    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        $authorizationHeader = $request->getHeader('Authorization');

        if (! $this->hasBearerScheme($authorizationHeader)) {
            return $next->handleRequest($request);
        }

        $token = $this->extractBearerToken($authorizationHeader);

        if ($token === null) {
            return $next->handleRequest($request);
        }

        /** @var AuthenticationManager $auth */
        $auth = App::make(AuthenticationManager::class);

        if (! $auth->validate($token)) {
            return $next->handleRequest($request);
        }

        return $this->unauthorized();
    }

    protected function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthorized',
        ], HttpStatus::UNAUTHORIZED)->send();
    }
}

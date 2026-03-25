<?php

declare(strict_types=1);

namespace Phenix\Http\Middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Phenix\Facades\Config;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Contracts\HeaderBuilder;

class ResponseHeaders implements Middleware
{
    /**
     * @var array<int, HeaderBuilder>
     */
    protected array $builders;

    public function __construct()
    {
        $builders = Config::get('app.response.headers', []);

        foreach ($builders as $builder) {
            assert(is_subclass_of($builder, HeaderBuilder::class));

            $this->builders[] = new $builder();
        }
    }

    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        $response = $next->handleRequest($request);

        if ($response->getStatus() >= HttpStatus::MULTIPLE_CHOICES->value && $response->getStatus() < HttpStatus::BAD_REQUEST->value) {
            return $response;
        }

        foreach ($this->builders as $builder) {
            $builder->apply($response);
        }

        return $response;
    }
}

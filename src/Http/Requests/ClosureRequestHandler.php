<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response as ServerResponse;
use Closure;
use Phenix\Http\FormRequest;
use Phenix\Http\Request;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class ClosureRequestHandler implements RequestHandler
{
    public function __construct(
        private readonly Closure $closure
    ) {
    }

    public function handleRequest(ServerRequest $request): ServerResponse
    {
        $formRequest = $this->resolveFormRequest($request);

        if ($formRequest instanceof FormRequest) {
            if (! $formRequest->isValid()) {
                return response()
                    ->json($formRequest->errors(), HttpStatus::UNPROCESSABLE_ENTITY)
                    ->send();
            }

            return ($this->closure)($formRequest)->send();
        }

        return ($this->closure)($formRequest)->send();
    }

    private function resolveFormRequest(ServerRequest $request): Request|FormRequest
    {
        $reflector = new ReflectionFunction($this->closure);

        /** @var ReflectionParameter|null $parameter */
        $parameter = $reflector->getParameters()[0] ?? null;

        if (! $parameter) {
            return new Request($request);
        }

        /** @var ReflectionNamedType|null $type */
        $type = $parameter->getType();
        $className = $type?->getName();

        if ($className && is_subclass_of($className, FormRequest::class)) {
            return new $className($request);
        }

        return new Request($request);
    }
}

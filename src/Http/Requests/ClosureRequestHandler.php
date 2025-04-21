<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response as ServerResponse;
use Closure;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\FormRequest;

class ClosureRequestHandler implements RequestHandler
{
    public function __construct(
        private readonly Closure $closure,
        private readonly string $formRequest
    ) {
    }

    public function handleRequest(ServerRequest $request): ServerResponse
    {
        $formRequest = new $this->formRequest($request);

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
}

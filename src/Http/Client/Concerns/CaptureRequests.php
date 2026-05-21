<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Concerns;

use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Closure;
use Phenix\Http\Client\Response;

use function is_array;
use function is_string;

trait CaptureRequests
{
    protected Closure|null $fakeResponse = null;

    /**
     * @var array<int, array{condition: Closure, response: Closure}>
     */
    protected array $fakeResponses = [];

    public function fake(Closure|null $response = null): self
    {
        $this->fakeResponse = $response ?? fn (): null => null;

        return $this;
    }

    public function fakeWhen(Closure $condition, Closure $response): self
    {
        $this->fakeResponses[] = [
            'condition' => $condition,
            'response' => $response,
        ];

        return $this;
    }

    protected function getFakeResponse(Request $request): Response|null
    {
        foreach ($this->fakeResponses as $fake) {
            if (($fake['condition'])($request, $this)) {
                return $this->normalizeFakeResponse(($fake['response'])($request, $this), $request);
            }
        }

        if ($this->fakeResponse !== null) {
            return $this->normalizeFakeResponse(($this->fakeResponse)($request, $this), $request);
        }

        return null;
    }

    protected function normalizeFakeResponse(mixed $response, Request $request): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if ($response instanceof AmpResponse) {
            return new Response($response);
        }

        $headers = [];
        $body = $response;

        if (is_array($response)) {
            $headers['Content-Type'] = 'application/json';
            $body = json_encode($response);
        }

        return new Response(new AmpResponse(
            '1.1',
            200,
            null,
            $headers,
            is_string($body) ? $body : '',
            $request
        ));
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Closure;
use Phenix\Data\Collection;
use Phenix\Http\Client\Constants\ProtocolVersion;

use function is_array;
use function is_string;

class HttpClientTestLogger
{
    protected Closure|null $fakeResponse = null;

    protected bool $faking = false;

    /**
     * @var array<int, array{condition: Closure, response: Closure}>
     */
    protected array $fakeResponses = [];

    /**
     * @var Collection<Request>
     */
    protected Collection $requests;

    public function fake(Closure|null $response = null): void
    {
        $this->faking = true;
        $this->fakeResponse = $response ?? fn (): null => null;
    }

    public function fakeWhen(Closure $condition, Closure $response): void
    {
        $this->fakeResponses[] = [
            'condition' => $condition,
            'response' => $response,
        ];
    }

    public function record(Request $request): void
    {
        if (! $this->shouldRecordRequests($request)) {
            return;
        }

        $this->getRequestLog()->add($request);
    }

    /**
     * @return Collection<Request>
     */
    public function getRequestLog(): Collection
    {
        if (! isset($this->requests)) {
            $this->requests = Collection::fromArray([]);
        }

        return $this->requests;
    }

    public function resetRequestLog(): void
    {
        $this->requests = Collection::fromArray([]);
    }

    public function resetFaking(): void
    {
        $this->fakeResponse = null;
        $this->faking = false;
        $this->fakeResponses = [];
        $this->resetRequestLog();
    }

    public function shouldRecordRequests(Request $request): bool
    {
        foreach ($this->fakeResponses as $fake) {
            if (($fake['condition'])($request, null)) {
                return true;
            }
        }

        return $this->faking;
    }

    public function getFakeResponse(Request $request, HttpClient|null $client = null): Response|null
    {
        [$matched, $response] = $this->findFakeResponse($request, $client);

        if (! $matched) {
            return null;
        }

        return $this->normalizeFakeResponse($response, $request);
    }

    public function getFakeStreamResponse(Request $request, HttpClient|null $client = null): StreamResponse|null
    {
        [$matched, $response] = $this->findFakeResponse($request, $client);

        if (! $matched) {
            return null;
        }

        return $this->normalizeFakeStreamResponse($response, $request);
    }

    /**
     * @return array{bool, mixed}
     */
    protected function findFakeResponse(Request $request, HttpClient|null $client): array
    {
        foreach ($this->fakeResponses as $fake) {
            if (($fake['condition'])($request, $client)) {
                return [true, ($fake['response'])($request, $client)];
            }
        }

        if ($this->faking && $this->fakeResponse !== null) {
            return [true, ($this->fakeResponse)($request, $client)];
        }

        return [false, null];
    }

    protected function normalizeFakeResponse(mixed $response, Request $request): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if ($response instanceof StreamResponse) {
            return new Response($response->getClientResponse());
        }

        return $response instanceof AmpResponse
            ? new Response($response)
            : new Response($this->makeClientResponse($response, $request));
    }

    protected function normalizeFakeStreamResponse(mixed $response, Request $request): StreamResponse
    {
        if ($response instanceof StreamResponse) {
            return $response;
        }

        if ($response instanceof Response) {
            return new StreamResponse($this->makeClientResponse(
                $response->body(),
                $request,
                $response->status(),
                $response->headers()
            ));
        }

        return $response instanceof AmpResponse
            ? new StreamResponse($response)
            : new StreamResponse($this->makeClientResponse($response, $request));
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function makeClientResponse(
        mixed $response,
        Request $request,
        int $status = 200,
        array $headers = []
    ): AmpResponse {
        $body = $response;

        if (is_array($response)) {
            $headers['Content-Type'] = 'application/json';
            $body = json_encode($response);
        }

        return new AmpResponse(
            ProtocolVersion::V1_1->value,
            $status,
            null,
            $headers,
            is_string($body) ? $body : '',
            $request
        );
    }
}

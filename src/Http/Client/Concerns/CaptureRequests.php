<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Concerns;

use Amp\Http\Client\Request;
use Closure;
use Phenix\App;
use Phenix\Data\Collection;
use Phenix\Http\Client\HttpClientTestLogger;
use Phenix\Http\Client\Response;
use Phenix\Http\Client\StreamResponse;

trait CaptureRequests
{
    protected HttpClientTestLogger|null $requestLogger = null;

    public function fake(Closure|null $response = null): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->getRequestLogger()->fake($response);
    }

    public function fakeWhen(Closure $condition, Closure $response): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->getRequestLogger()->fakeWhen($condition, $response);
    }

    /**
     * @return Collection<Request>
     */
    public function getRequestLog(): Collection
    {
        return $this->getRequestLogger()->getRequestLog();
    }

    public function resetRequestLog(): void
    {
        $this->getRequestLogger()->resetRequestLog();
    }

    public function resetFaking(): void
    {
        $this->getRequestLogger()->resetFaking();
    }

    protected function getRequestLogger(): HttpClientTestLogger
    {
        return $this->requestLogger ??= App::make(HttpClientTestLogger::class);
    }

    protected function recordRequest(Request $request): void
    {
        $this->getRequestLogger()->record($request);
    }

    protected function getFakeResponse(Request $request): Response|null
    {
        return $this->getRequestLogger()->getFakeResponse($request, $this);
    }

    protected function getFakeStreamResponse(Request $request): StreamResponse|null
    {
        return $this->getRequestLogger()->getFakeStreamResponse($request, $this);
    }
}

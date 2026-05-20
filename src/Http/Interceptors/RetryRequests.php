<?php

declare(strict_types=1);

namespace Phenix\Http\Interceptors;

use Amp\Cancellation;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Closure;

use function Amp\delay;

final class RetryRequests implements ApplicationInterceptor
{
    private int $attempts;

    private Closure|null $when;

    public function __construct(
        int $attempts,
        private readonly Closure|int $sleepMilliseconds = 0,
        callable|null $when = null
    ) {
        $this->attempts = max(1, $attempts);
        $this->when = $when === null ? null : Closure::fromCallable($when);
    }

    public function request(
        Request $request,
        Cancellation $cancellation,
        DelegateHttpClient $httpClient
    ): Response {
        $exception = null;

        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            $clonedRequest = clone $request;

            try {
                return $httpClient->request($request, $cancellation);
            } catch (HttpException $exception) {
                if (! $this->shouldRetry($exception, $request, $attempt)) {
                    throw $exception;
                }

                $this->delayBeforeRetry($attempt, $exception, $request, $cancellation);

                $request = $clonedRequest;
            }
        }

        throw $exception;
    }

    private function shouldRetry(HttpException $exception, Request $request, int $attempt): bool
    {
        if ($attempt >= $this->attempts) {
            return false;
        }

        if (! $request->isIdempotent() && ! $request->isUnprocessed()) {
            return false;
        }

        if ($this->when !== null) {
            return (bool) ($this->when)($exception, $request, $attempt);
        }

        return true;
    }

    private function delayBeforeRetry(
        int $attempt,
        HttpException $exception,
        Request $request,
        Cancellation $cancellation
    ): void {
        $milliseconds = $this->sleepMilliseconds instanceof Closure
            ? ($this->sleepMilliseconds)($attempt, $exception, $request)
            : $this->sleepMilliseconds;

        if (! is_numeric($milliseconds) || $milliseconds <= 0) {
            return;
        }

        delay(((float) $milliseconds) / 1000, cancellation: $cancellation);
    }
}

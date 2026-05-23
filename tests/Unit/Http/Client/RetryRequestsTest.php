<?php

declare(strict_types=1);

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Internal\EventInvoker;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\NullCancellation;
use Phenix\Http\Client\Interceptors\RetryRequests;

it('retries failed requests until they succeed', function (): void {
    $request = new Request('https://phenix.test');
    $calls = 0;

    $client = new class ($calls) implements DelegateHttpClient {
        public function __construct(private int &$calls)
        {
        }

        public function request(Request $request, Cancellation $cancellation): Response
        {
            $this->calls++;

            if ($this->calls < 3) {
                throw new HttpException('Connection failed.');
            }

            return new Response('1.1', 200, null, [], '', $request);
        }
    };

    $response = (new RetryRequests(3))->request($request, new NullCancellation(), $client);

    expect($response->getStatus())->toBe(200)
        ->and($calls)->toBe(3);
});

it('stops retrying when the retry condition rejects the exception', function (): void {
    $request = new Request('https://phenix.test');
    $calls = 0;

    $client = new class ($calls) implements DelegateHttpClient {
        public function __construct(private int &$calls)
        {
        }

        public function request(Request $request, Cancellation $cancellation): Response
        {
            $this->calls++;

            throw new HttpException('Connection failed.');
        }
    };

    expect(fn () => (new RetryRequests(3, when: fn () => false))->request(
        $request,
        new NullCancellation(),
        $client
    ))->toThrow(HttpException::class)
        ->and($calls)->toBe(1);
});

it('stops retrying when the maximum attempts are exhausted', function (): void {
    $request = new Request('https://phenix.test');
    $calls = 0;

    $client = new class ($calls) implements DelegateHttpClient {
        public function __construct(private int &$calls)
        {
        }

        public function request(Request $request, Cancellation $cancellation): Response
        {
            $this->calls++;

            throw new HttpException('Connection failed.');
        }
    };

    expect(fn () => (new RetryRequests(1))->request(
        $request,
        new NullCancellation(),
        $client
    ))->toThrow(HttpException::class)
        ->and($calls)->toBe(1);
});

it('does not retry non idempotent requests that were already processed', function (): void {
    $request = new Request('https://phenix.test', 'POST');
    $calls = 0;

    $client = new class ($calls) implements DelegateHttpClient {
        public function __construct(private int &$calls)
        {
        }

        public function request(Request $request, Cancellation $cancellation): Response
        {
            $this->calls++;

            $exception = new HttpException('Connection failed.');

            EventInvoker::get()->requestFailed($request, $exception);

            throw $exception;
        }
    };

    expect(fn () => (new RetryRequests(3))->request(
        $request,
        new NullCancellation(),
        $client
    ))->toThrow(HttpException::class)
        ->and($calls)->toBe(1);
});

it('uses sleep closures before retrying requests', function (): void {
    $request = new Request('https://phenix.test');
    $calls = 0;
    $sleepCalls = [];

    $client = new class ($calls) implements DelegateHttpClient {
        public function __construct(private int &$calls)
        {
        }

        public function request(Request $request, Cancellation $cancellation): Response
        {
            $this->calls++;

            if ($this->calls === 1) {
                throw new HttpException('Connection failed.');
            }

            return new Response('1.1', 200, null, [], '', $request);
        }
    };

    $response = (new RetryRequests(
        2,
        function (int $attempt, HttpException $exception, Request $request) use (&$sleepCalls): string {
            $sleepCalls[] = [$attempt, $exception->getMessage(), (string) $request->getUri()];

            return 'invalid';
        }
    ))->request($request, new NullCancellation(), $client);

    expect($response->getStatus())->toBe(200)
        ->and($calls)->toBe(2)
        ->and($sleepCalls)->toBe([
            [1, 'Connection failed.', 'https://phenix.test'],
        ]);
});

it('delays before retrying when configured with positive milliseconds', function (): void {
    $request = new Request('https://phenix.test');
    $calls = 0;

    $client = new class ($calls) implements DelegateHttpClient {
        public function __construct(private int &$calls)
        {
        }

        public function request(Request $request, Cancellation $cancellation): Response
        {
            $this->calls++;

            if ($this->calls === 1) {
                throw new HttpException('Connection failed.');
            }

            return new Response('1.1', 200, null, [], '', $request);
        }
    };

    $startedAt = microtime(true);

    $response = (new RetryRequests(2, 20))->request($request, new NullCancellation(), $client);

    expect($response->getStatus())->toBe(200)
        ->and($calls)->toBe(2)
        ->and(microtime(true) - $startedAt)->toBeGreaterThanOrEqual(0.015);
});

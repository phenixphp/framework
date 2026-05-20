<?php

declare(strict_types=1);

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\NullCancellation;
use Phenix\Http\Interceptors\RetryRequests;

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

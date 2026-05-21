<?php

declare(strict_types=1);

use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Phenix\Facades\Http;
use Phenix\Http\Client\Response;

it('fakes all http client requests with an empty successful response', function (): void {
    Http::fake();

    $response = Http::get('https://phenix.test/users');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->ok())->toBeTrue()
        ->and($response->body())->toBe('');
});

it('fakes all http client requests using a closure response', function (): void {
    Http::fake(fn (Request $request): array => [
        'uri' => (string) $request->getUri(),
    ]);

    $response = Http::get('https://phenix.test/users');

    expect($response->json('uri'))->toBe('https://phenix.test/users');
});

it('fakes http client requests when a condition matches', function (): void {
    Http::fake(fn (): string => 'fallback');
    Http::fakeWhen(
        fn (Request $request): bool => str_contains((string) $request->getUri(), '/users'),
        fn (): array => ['resource' => 'users']
    );

    expect(Http::get('https://phenix.test/users')->json('resource'))->toBe('users')
        ->and(Http::get('https://phenix.test/posts')->body())->toBe('fallback');
});

it('accepts wrapped and amp responses from fake callbacks', function (): void {
    $request = new Request('https://phenix.test/users');
    $wrapped = new Response(new AmpResponse('1.1', 200, null, [], 'wrapped', $request));

    Http::fake(fn (): Response => $wrapped);
    expect(Http::get('https://phenix.test/users'))->toBe($wrapped);

    Http::fake(fn (Request $request): AmpResponse => new AmpResponse(
        '1.1',
        200,
        null,
        [],
        'amp',
        $request
    ));

    expect(Http::get('https://phenix.test/users')->body())->toBe('amp');
});

it('applies mockery expectations through the http facade', function (): void {
    $response = new Response(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        'mocked',
        new Request('https://phenix.test/users')
    ));

    /** @var \Mockery\Expectation $expectation */
    $expectation = Http::expect('get');
    $expectation
        ->once()
        ->with('https://phenix.test/users')
        ->andReturn($response);

    expect(Http::get('https://phenix.test/users'))->toBe($response);

    Mockery::close();
});

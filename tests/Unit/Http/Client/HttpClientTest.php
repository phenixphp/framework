<?php

declare(strict_types=1);

use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Phenix\Contracts\Arrayable;
use Phenix\Facades\Http;
use Phenix\Http\Client\HttpClient;
use Phenix\Http\Client\Response;

use function Amp\ByteStream\buffer;

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

it('builds get and head requests with query parameters', function (): void {
    $client = new HttpClient();
    $requests = [];

    $client->fake(function (Request $request) use (&$requests): string {
        $requests[] = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ];

        return 'ok';
    });

    expect($client->get('https://phenix.test/users', ['page' => '1'])->body())->toBe('ok')
        ->and($client->head('https://phenix.test/status', ['check' => 'health'])->body())->toBe('ok')
        ->and($requests)->toBe([
            ['method' => 'GET', 'uri' => 'https://phenix.test/users?page=1'],
            ['method' => 'HEAD', 'uri' => 'https://phenix.test/status?check=health'],
        ]);
});

it('builds write requests with the expected methods and bodies', function (): void {
    $client = new HttpClient();
    $requests = [];

    $client->fake(function (Request $request) use (&$requests): string {
        $requests[] = [
            'method' => $request->getMethod(),
            'body' => buffer($request->getBody()->getContent()),
        ];

        return 'ok';
    });

    $arrayable = new class () implements Arrayable {
        public function toArray(): array
        {
            return ['arrayable' => true];
        }
    };

    $client->post('https://phenix.test/users', ['name' => 'Taylor']);
    $client->put('https://phenix.test/users/1', $arrayable);
    $client->patch('https://phenix.test/users/1', 'patched');
    $client->delete('https://phenix.test/users/1', ['force' => true]);

    expect($requests)->toBe([
        ['method' => 'POST', 'body' => '{"name":"Taylor"}'],
        ['method' => 'PUT', 'body' => '{"arrayable":true}'],
        ['method' => 'PATCH', 'body' => 'patched'],
        ['method' => 'DELETE', 'body' => '{"force":true}'],
    ]);
});

it('applies headers and authentication helpers to requests', function (): void {
    $client = new HttpClient();
    $headers = [];

    $client->fake(function (Request $request) use (&$headers): string {
        $headers[] = [
            'accept' => $request->getHeader('accept'),
            'trace' => $request->getHeader('x-trace-id'),
            'authorization' => $request->getHeader('authorization'),
        ];

        return 'ok';
    });

    $client
        ->withHeaders(['Accept' => 'application/json'])
        ->withHeaders(['X-Trace-Id' => 'trace-1'])
        ->withBasicAuth('phenix', 'secret')
        ->get('https://phenix.test/basic');

    $client
        ->withDigestAuth('digest-user', 'digest-secret')
        ->get('https://phenix.test/digest');

    $client
        ->withToken('token-value', 'Token')
        ->get('https://phenix.test/token');

    expect($headers)->toBe([
        [
            'accept' => 'application/json',
            'trace' => 'trace-1',
            'authorization' => 'Basic ' . base64_encode('phenix:secret'),
        ],
        [
            'accept' => 'application/json',
            'trace' => 'trace-1',
            'authorization' => 'Digest ' . base64_encode('digest-user:digest-secret'),
        ],
        [
            'accept' => 'application/json',
            'trace' => 'trace-1',
            'authorization' => 'Token token-value',
        ],
    ]);
});

it('prefers matching conditional fakes over the global fake response', function (): void {
    $client = new HttpClient();

    $client->fake(fn (): string => 'fallback');
    $client->fakeWhen(
        fn (Request $request): bool => str_contains((string) $request->getUri(), '/users'),
        fn (): array => ['matched' => true]
    );

    expect($client->get('https://phenix.test/users')->json('matched'))->toBeTrue()
        ->and($client->get('https://phenix.test/posts')->body())->toBe('fallback');
});

it('configures retry fluently for custom and default retry attempts', function (): void {
    $client = new HttpClient();

    expect($client->retry(3))->toBe($client)
        ->and($client->retry(0))->toBe($client)
        ->and($client->retry(-1))->toBe($client);
});

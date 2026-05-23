<?php

declare(strict_types=1);

use Amp\ByteStream\ReadableIterableStream;
use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClient as AmpHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Phenix\Facades\File;
use Phenix\Facades\Http;
use Phenix\Http\Client\HttpClient;
use Phenix\Http\Client\StreamResponse;
use Phenix\Http\Constants\HttpMethod;

use function Amp\ByteStream\buffer;

it('streams response chunks without buffering the wrapper', function (): void {
    $ampResponse = new AmpResponse(
        '1.1',
        200,
        null,
        ['Content-Type' => 'application/octet-stream'],
        'phenix-stream',
        new Request('https://phenix.test/download')
    );

    $response = new StreamResponse($ampResponse);

    expect($response->getClientResponse())->toBe($ampResponse)
        ->and($response->status())->toBe(200)
        ->and($response->ok())->toBeTrue()
        ->and($response->header('content-type'))->toBe('application/octet-stream')
        ->and($response->read())->toBe('phenix-stream')
        ->and($response->read())->toBeNull();
});

it('exposes streamed response state and headers', function (): void {
    $redirect = new StreamResponse(new AmpResponse(
        '1.1',
        302,
        null,
        ['Location' => 'https://phenix.test/next'],
        '',
        new Request('https://phenix.test/download')
    ));

    $clientError = new StreamResponse(new AmpResponse(
        '1.1',
        404,
        null,
        ['Content-Type' => 'application/json'],
        '',
        new Request('https://phenix.test/download')
    ));

    expect($redirect->redirect())->toBeTrue()
        ->and($redirect->successful())->toBeFalse()
        ->and($redirect->failed())->toBeFalse()
        ->and($redirect->header('location'))->toBe('https://phenix.test/next')
        ->and($redirect->headers())->toBe(['location' => ['https://phenix.test/next']])
        ->and($clientError->clientError())->toBeTrue()
        ->and($clientError->serverError())->toBeFalse()
        ->and($clientError->failed())->toBeTrue()
        ->and($clientError->header('content-type'))->toBe('application/json')
        ->and($clientError->headers())->toBe(['content-type' => ['application/json']]);
});

it('iterates streamed chunks and reports bytes read', function (): void {
    $response = new StreamResponse(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        'abc',
        new Request('https://phenix.test/download')
    ));

    $chunks = [];

    $result = $response->each(function (string $chunk, int $bytes) use (&$chunks): void {
        $chunks[] = [$chunk, $bytes];
    });

    expect($result)->toBe($response)
        ->and($chunks)->toBe([['abc', 3]]);
});

it('iterates streamed chunks and reports cumulative bytes read', function (): void {
    $response = new StreamResponse(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        new ReadableIterableStream(['abc', 'de']),
        new Request('https://phenix.test/download')
    ));

    $chunks = [];

    $response->each(function (string $chunk, int $totalBytesRead) use (&$chunks): void {
        $chunks[] = [$chunk, $totalBytesRead];
    });

    expect($chunks)->toBe([['abc', 3], ['de', 5]]);
});

it('prevents nested reads while iterating streamed chunks', function (): void {
    $response = new StreamResponse(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        new ReadableIterableStream(['abc', 'de']),
        new Request('https://phenix.test/download')
    ));

    expect(fn () => $response->each(
        fn (string $chunk, int $bytes, StreamResponse $stream): string|null => $stream->read()
    ))->toThrow(LogicException::class);

    expect($response->read())->toBe('de');
});

it('saves streamed responses to disk', function (): void {
    $response = new StreamResponse(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        'download-body',
        new Request('https://phenix.test/download')
    ));

    $path = tempnam(sys_get_temp_dir(), 'phenix-stream-');
    $progress = [];

    $bytes = $response->save($path, function (int $bytes) use (&$progress): void {
        $progress[] = $bytes;
    });

    expect($bytes)->toBe(13)
        ->and(File::get($path))->toBe('download-body')
        ->and($progress)->toBe([13]);

    unlink($path);
});

it('removes partial files when saving a streamed response fails', function (): void {
    $response = new StreamResponse(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        new ReadableIterableStream(['partial', 'body']),
        new Request('https://phenix.test/download')
    ));

    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phenix-stream-' . bin2hex(random_bytes(8));
    mkdir($directory);

    $path = $directory . DIRECTORY_SEPARATOR . 'download.txt';

    expect(fn () => $response->save(
        $path,
        fn (): never => throw new RuntimeException('Progress failed.')
    ))->toThrow(RuntimeException::class, 'Progress failed.');

    expect(file_exists($path))->toBeFalse()
        ->and(glob($directory . DIRECTORY_SEPARATOR . '.phenix-stream-*.tmp'))->toBe([]);

    rmdir($directory);
});

it('streams requests through the http client callback', function (): void {
    $client = new HttpClient();
    $captured = [];

    $delegate = new class ($captured) implements DelegateHttpClient {
        public function __construct(private array &$captured)
        {
        }

        public function request(Request $request, Cancellation $cancellation): AmpResponse
        {
            $this->captured[] = [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => buffer($request->getBody()->getContent()),
                'bodySizeLimit' => $request->getBodySizeLimit(),
                'transferTimeout' => $request->getTransferTimeout(),
            ];

            return new AmpResponse('1.1', 200, null, [], 'stream-body', $request);
        }
    };

    $client->withClient(new AmpHttpClient($delegate, []));

    $body = $client->stream(
        'https://phenix.test/download',
        fn (StreamResponse $response): string|null => $response->read(),
        queryParameters: ['token' => 'abc'],
        bodySizeLimit: 128 * 1024 * 1024,
        transferTimeout: 120
    );

    $postBody = $client->stream(
        'https://phenix.test/export',
        fn (StreamResponse $response): string|null => $response->read(),
        method: HttpMethod::POST,
        data: ['format' => 'csv']
    );

    expect($body)->toBe('stream-body')
        ->and($postBody)->toBe('stream-body')
        ->and($captured)->toBe([
            [
                'method' => 'GET',
                'uri' => 'https://phenix.test/download?token=abc',
                'body' => '',
                'bodySizeLimit' => 128 * 1024 * 1024,
                'transferTimeout' => 120.0,
            ],
            [
                'method' => 'POST',
                'uri' => 'https://phenix.test/export',
                'body' => '{"format":"csv"}',
                'bodySizeLimit' => 10485760,
                'transferTimeout' => 10.0,
            ],
        ]);
});

it('fakes facade streamed requests with the global fake response', function (): void {
    Http::fake(fn (Request $request): string => 'fake stream: ' . $request->getMethod());

    $response = Http::stream('https://phenix.test/download');

    expect($response)->toBeInstanceOf(StreamResponse::class)
        ->and($response->read())->toBe('fake stream: GET')
        ->and($response->read())->toBeNull()
        ->and(Http::getRequestLog())->toHaveCount(1)
        ->and((string) Http::getRequestLog()->first()->getUri())->toBe('https://phenix.test/download');
});

it('prefers facade conditional fakes for streamed requests', function (): void {
    Http::fake(fn (): string => 'fallback-stream');
    Http::fakeWhen(
        fn (Request $request): bool => str_contains((string) $request->getUri(), '/download'),
        fn (): string => 'matched-stream'
    );

    $matched = Http::stream('https://phenix.test/download');
    $fallback = Http::stream('https://phenix.test/archive');

    expect($matched)->toBeInstanceOf(StreamResponse::class)
        ->and($matched->read())->toBe('matched-stream')
        ->and($fallback)->toBeInstanceOf(StreamResponse::class)
        ->and($fallback->read())->toBe('fallback-stream');
});

it('fakes streamed requests before touching the amp client', function (): void {
    $client = new HttpClient();

    $delegate = new class () implements DelegateHttpClient {
        public function request(Request $request, Cancellation $cancellation): AmpResponse
        {
            throw new RuntimeException('The real Amp client should not receive faked stream requests.');
        }
    };

    $client
        ->withClient(new AmpHttpClient($delegate, []))
        ->fake(function (Request $request): array {
            return [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => buffer($request->getBody()->getContent()),
                'bodySizeLimit' => $request->getBodySizeLimit(),
                'transferTimeout' => $request->getTransferTimeout(),
            ];
        });

    $body = $client->stream(
        'https://phenix.test/export',
        fn (StreamResponse $response): string|null => $response->read(),
        queryParameters: ['token' => 'abc'],
        bodySizeLimit: 128 * 1024 * 1024,
        transferTimeout: 120,
        method: HttpMethod::POST,
        data: ['format' => 'csv']
    );

    expect(json_decode($body, true))->toBe([
        'method' => 'POST',
        'uri' => 'https://phenix.test/export?token=abc',
        'body' => '{"format":"csv"}',
        'bodySizeLimit' => 128 * 1024 * 1024,
        'transferTimeout' => 120,
    ]);
});

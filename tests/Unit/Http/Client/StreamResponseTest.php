<?php

declare(strict_types=1);

use Amp\ByteStream\ReadableIterableStream;
use Amp\Http\Client\Form;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Phenix\Contracts\Arrayable;
use Phenix\Facades\File;
use Phenix\Http\Client\HttpClient;
use Phenix\Http\Client\StreamResponse;
use Phenix\Http\Constants\HttpMethod;
use Psr\Http\Message\UriInterface;

it('streams response chunks without buffering the wrapper', function (): void {
    $response = new StreamResponse(new AmpResponse(
        '1.1',
        200,
        null,
        ['Content-Type' => 'application/octet-stream'],
        'phenix-stream',
        new Request('https://phenix.test/download')
    ));

    expect($response->status())->toBe(200)
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
    $client = new class () extends HttpClient {
        public int|null $bodySizeLimit = null;

        public float|null $transferTimeout = null;

        protected function streamCall(
            HttpMethod $method,
            UriInterface|string $url,
            Form|Arrayable|array|string|null $data = null,
            array|null $queryParameters = null,
            int|null $bodySizeLimit = null,
            float|null $transferTimeout = null
        ): StreamResponse {
            $this->bodySizeLimit = $bodySizeLimit;
            $this->transferTimeout = $transferTimeout;

            return new StreamResponse(new AmpResponse(
                '1.1',
                200,
                null,
                [],
                (string) $url,
                new Request((string) $url)
            ));
        }
    };

    $body = $client->stream(
        'https://phenix.test/download',
        fn (StreamResponse $response): string|null => $response->read(),
        bodySizeLimit: 128 * 1024 * 1024,
        transferTimeout: 120
    );

    expect($body)->toBe('https://phenix.test/download')
        ->and($client->bodySizeLimit)->toBe(128 * 1024 * 1024)
        ->and($client->transferTimeout)->toBe(120.0);
});

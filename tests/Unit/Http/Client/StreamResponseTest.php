<?php

declare(strict_types=1);

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

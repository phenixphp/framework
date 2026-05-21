<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Phenix\Contracts\Arrayable;
use Phenix\Http\Client\HttpClient;
use Phenix\Http\Client\Pool;
use Phenix\Http\Client\Response;
use Phenix\Http\Constants\HttpMethod;
use Psr\Http\Message\UriInterface;

use function Amp\delay;

it('executes pooled requests concurrently and preserves keys', function (): void {
    $client = new class () extends HttpClient {
        protected function call(
            HttpMethod $method,
            UriInterface|string $url,
            Form|Arrayable|array|string|null $data = null,
            array|null $queryParameters = null
        ): Response {
            delay(0.01);

            return new Response(new AmpResponse(
                '1.1',
                200,
                null,
                [],
                (string) $url,
                new Request((string) $url)
            ));
        }
    };

    $responses = $client->pool(fn (Pool $pool): array => [
        'google' => $pool->get('https://google.com'),
        'github' => $pool->get('https://github.com'),
    ]);

    expect(array_keys($responses))->toBe(['google', 'github'])
        ->and($responses['google'])->toBeInstanceOf(Response::class)
        ->and($responses['google']->body())->toBe('https://google.com')
        ->and($responses['github']->body())->toBe('https://github.com');
});

it('limits pooled request concurrency', function (): void {
    $active = 0;
    $maxActive = 0;

    $client = new class ($active, $maxActive) extends HttpClient {
        public function __construct(private int &$active, private int &$maxActive)
        {
            parent::__construct();
        }

        protected function call(
            HttpMethod $method,
            UriInterface|string $url,
            Form|Arrayable|array|string|null $data = null,
            array|null $queryParameters = null
        ): Response {
            $this->active++;
            $this->maxActive = max($this->maxActive, $this->active);

            delay(0.01);

            $this->active--;

            return new Response(new AmpResponse(
                '1.1',
                200,
                null,
                [],
                (string) $url,
                new Request((string) $url)
            ));
        }
    };

    $responses = $client->pool(fn (Pool $pool): array => [
        $pool->get('https://phenix.test/1'),
        $pool->get('https://phenix.test/2'),
        $pool->get('https://phenix.test/3'),
        $pool->get('https://phenix.test/4'),
    ], concurrency: 2);

    expect($responses)->toHaveCount(4)
        ->and($maxActive)->toBeLessThanOrEqual(2);
});

it('creates request callbacks for every supported http method', function (): void {
    $client = new class () extends HttpClient {
        public array $calls = [];

        protected function call(
            HttpMethod $method,
            UriInterface|string $url,
            Form|Arrayable|array|string|null $data = null,
            array|null $queryParameters = null
        ): Response {
            $this->calls[] = [
                'method' => $method,
                'url' => (string) $url,
                'data' => $data,
                'query' => $queryParameters,
            ];

            return new Response(new AmpResponse(
                '1.1',
                200,
                null,
                [],
                (string) $url,
                new Request((string) $url)
            ));
        }
    };

    $pool = new Pool();

    ($pool->head('https://phenix.test/head', ['status' => 'pending']))($client);
    ($pool->post('https://phenix.test/post', ['name' => 'Phenix']))($client);
    ($pool->put('https://phenix.test/put', ['name' => 'Framework']))($client);
    ($pool->patch('https://phenix.test/patch', ['enabled' => true]))($client);
    ($pool->delete('https://phenix.test/delete', ['force' => true]))($client);

    expect($client->calls)->toBe([
        [
            'method' => HttpMethod::HEAD,
            'url' => 'https://phenix.test/head',
            'data' => null,
            'query' => ['status' => 'pending'],
        ],
        [
            'method' => HttpMethod::POST,
            'url' => 'https://phenix.test/post',
            'data' => ['name' => 'Phenix'],
            'query' => null,
        ],
        [
            'method' => HttpMethod::PUT,
            'url' => 'https://phenix.test/put',
            'data' => ['name' => 'Framework'],
            'query' => null,
        ],
        [
            'method' => HttpMethod::PATCH,
            'url' => 'https://phenix.test/patch',
            'data' => ['enabled' => true],
            'query' => null,
        ],
        [
            'method' => HttpMethod::DELETE,
            'url' => 'https://phenix.test/delete',
            'data' => ['force' => true],
            'query' => null,
        ],
    ]);
});

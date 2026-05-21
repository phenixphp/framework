<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\EventListener;
use Amp\Http\Client\EventListener\LogHttpArchive;
use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClient as AmpHttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Closure;
use Phenix\Contracts\Arrayable;
use Phenix\Http\Client\Concerns\CaptureRequests;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Http\Interceptors\RetryRequests;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;

use function Amp\async;
use function Amp\Future\await;
use function is_array;

class HttpClient
{
    use CaptureRequests;

    protected AmpHttpClient $client;

    protected HttpClientBuilder $builder;

    protected Request $request;

    protected array $headers = [];

    public function __construct()
    {
        $this->builder = new HttpClientBuilder();
        $this->client = $this->builder->build();
        $this->headers = [];
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = [...$this->headers, ...$headers];

        return $this;
    }

    public function withBasicAuth(
        string $username,
        #[SensitiveParameter]
        string $password
    ): self {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("{$username}:{$password}");

        return $this;
    }

    public function withDigestAuth(
        string $username,
        #[SensitiveParameter]
        string $password
    ): self {
        $this->headers['Authorization'] = 'Digest ' . base64_encode("{$username}:{$password}");

        return $this;
    }

    public function withToken(#[SensitiveParameter] string $token, string $type = 'Bearer'): self
    {
        $this->headers['Authorization'] = "{$type} {$token}";

        return $this;
    }

    public function retry(int $times, Closure|int $sleepMilliseconds = 0, callable|null $when = null): self
    {
        if ($times <= 0) {
            $this->builder = $this->builder->retry(2);
            $this->client = $this->builder->build();

            return $this;
        }

        $this->builder = $this->builder
            ->retry(0)
            ->intercept(new RetryRequests($times, $sleepMilliseconds, $when));

        $this->client = $this->builder->build();

        return $this;
    }

    public function listen(EventListener $eventListener): self
    {
        $this->builder = $this->builder->listen($eventListener);
        $this->client = $this->builder->build();

        return $this;
    }

    public function log(string $path): self
    {
        $this->listen(new LogHttpArchive($path));

        return $this;
    }

    public function withTlsContext(ClientTlsContext $tlsContext): self
    {
        $connectContext = (new ConnectContext())->withTlsContext($tlsContext);
        $connectionFactory = new DefaultConnectionFactory(connectContext: $connectContext);

        $this->builder = $this->builder->usingPool(new UnlimitedConnectionPool($connectionFactory));
        $this->client = $this->builder->build();

        return $this;
    }

    public function withCertificate(
        string $certificate,
        string|null $key = null,
        string|null $ca = null,
        #[SensitiveParameter]
        string|null $passphrase = null,
        string $peerName = ''
    ): self {
        $tlsContext = (new ClientTlsContext($peerName))
            ->withCertificate(new Certificate($certificate, $key, $passphrase));

        if ($ca !== null) {
            $tlsContext = $tlsContext->withCaFile($ca);
        }

        return $this->withTlsContext($tlsContext);
    }

    public function get(UriInterface|string $url, array|null $queryParameters = null): Response
    {
        return $this->call(HttpMethod::GET, $url, queryParameters: $queryParameters);
    }

    public function head(UriInterface|string $url, array|null $queryParameters = null): Response
    {
        return $this->call(HttpMethod::HEAD, $url, queryParameters: $queryParameters);
    }

    public function post(UriInterface|string $url, Form|Arrayable|array|string $data = []): Response
    {
        return $this->call(HttpMethod::POST, $url, $data);
    }

    public function put(UriInterface|string $url, Form|Arrayable|array|string $data = []): Response
    {
        return $this->call(HttpMethod::PUT, $url, $data);
    }

    public function patch(UriInterface|string $url, Form|Arrayable|array|string $data = []): Response
    {
        return $this->call(HttpMethod::PATCH, $url, $data);
    }

    public function delete(UriInterface|string $url, Form|Arrayable|array|string $data = []): Response
    {
        return $this->call(HttpMethod::DELETE, $url, $data);
    }

    public function stream(
        UriInterface|string $url,
        Closure|null $callback = null,
        array|null $queryParameters = null,
        int|null $bodySizeLimit = null,
        float|null $transferTimeout = null
    ): mixed {
        $response = $this->streamCall(
            method: HttpMethod::GET,
            url: $url,
            queryParameters: $queryParameters,
            bodySizeLimit: $bodySizeLimit,
            transferTimeout: $transferTimeout
        );

        if ($callback !== null) {
            return $callback($response);
        }

        return $response;
    }

    /**
     * @param Closure(Pool): array<array-key, Closure(HttpClient): Response> $closure
     * @param int|null $concurrency
     * @return array<array-key, Response>
     */
    public function pool(Closure $closure, int|null $concurrency = 0): array
    {
        $requests = $closure(new Pool());
        $semaphore = $concurrency !== null && $concurrency > 0 ? new LocalSemaphore($concurrency) : null;
        $futures = [];

        foreach ($requests as $key => $request) {
            $futures[$key] = async(fn (): Response => $this->executePoolRequest($request, $semaphore));
        }

        return await($futures);
    }

    protected function call(
        HttpMethod $method,
        UriInterface|string $url,
        Form|Arrayable|array|string|null $data = null,
        array|null $queryParameters = null
    ): Response {
        $request = $this->createRequest($method, $url, $data, $queryParameters);

        if ($fake = $this->getFakeResponse($request)) {
            return $fake;
        }

        return new Response($this->client->request($request));
    }

    protected function streamCall(
        HttpMethod $method,
        UriInterface|string $url,
        Form|Arrayable|array|string|null $data = null,
        array|null $queryParameters = null,
        int|null $bodySizeLimit = null,
        float|null $transferTimeout = null
    ): StreamResponse {
        $request = $this->createRequest($method, $url, $data, $queryParameters);

        if ($bodySizeLimit !== null) {
            $request->setBodySizeLimit($bodySizeLimit);
        }

        if ($transferTimeout !== null) {
            $request->setTransferTimeout($transferTimeout);
        }

        return new StreamResponse($this->client->request($request));
    }

    protected function createRequest(
        HttpMethod $method,
        UriInterface|string $url,
        Form|Arrayable|array|string|null $data = null,
        array|null $queryParameters = null
    ): Request {
        $request = new Request($url, $method->value);
        $request->setHeaders($this->headers);

        if ($queryParameters !== null) {
            $request->setQueryParameters($queryParameters);
        }

        if ($data !== null) {
            $body = match (true) {
                $data instanceof Arrayable => json_encode($data->toArray()),
                is_array($data) => json_encode($data),
                default => $data,
            };

            $request->setBody($body);
        }

        return $request;
    }

    /**
     * @param Closure(HttpClient): Response $request
     */
    protected function executePoolRequest(Closure $request, Semaphore|null $semaphore): Response
    {
        if ($semaphore === null) {
            return $request($this);
        }

        $lock = $semaphore->acquire();

        try {
            return $request($this);
        } finally {
            $lock->release();
        }
    }
}

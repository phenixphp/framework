<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Phenix\Contracts\Arrayable;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Http\Interceptors\RetryRequests;
use Psr\Http\Message\UriInterface;

class Client
{
    protected HttpClient $client;

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
            $this->client = $this->builder->retry(2)->build();

            return $this;
        }

        $this->client = $this->builder
            ->retry(0)
            ->intercept(new RetryRequests($times, $sleepMilliseconds, $when))
            ->build();

        return $this;
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

    protected function call(
        HttpMethod $method,
        UriInterface|string $url,
        Form|Arrayable|array|string|null $data = null,
        array|null $queryParameters = null
    ): Response {
        return $this->client->request($this->createRequest($method, $url, $data, $queryParameters));
    }

    private function createRequest(
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
}

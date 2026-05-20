<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Phenix\Contracts\Arrayable;
use Phenix\Http\Constants\HttpMethod;
use Psr\Http\Message\UriInterface;

class Client
{
    protected HttpClient $client;

    protected Request $request;

    protected array $headers = [];

    public function __construct()
    {
        $this->client = HttpClientBuilder::buildDefault();
        $this->headers = [];
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;

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

        return $this->client->request($request);
    }
}

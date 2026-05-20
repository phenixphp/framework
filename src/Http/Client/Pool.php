<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\Http\Client\Form;
use Closure;
use Phenix\Contracts\Arrayable;
use Psr\Http\Message\UriInterface;

class Pool
{
    public function get(UriInterface|string $url, array|null $queryParameters = null): Closure
    {
        return fn (HttpClient $client): Response => $client->get($url, $queryParameters);
    }

    public function head(UriInterface|string $url, array|null $queryParameters = null): Closure
    {
        return fn (HttpClient $client): Response => $client->head($url, $queryParameters);
    }

    public function post(UriInterface|string $url, Form|Arrayable|array|string $data = []): Closure
    {
        return fn (HttpClient $client): Response => $client->post($url, $data);
    }

    public function put(UriInterface|string $url, Form|Arrayable|array|string $data = []): Closure
    {
        return fn (HttpClient $client): Response => $client->put($url, $data);
    }

    public function patch(UriInterface|string $url, Form|Arrayable|array|string $data = []): Closure
    {
        return fn (HttpClient $client): Response => $client->patch($url, $data);
    }

    public function delete(UriInterface|string $url, Form|Arrayable|array|string $data = []): Closure
    {
        return fn (HttpClient $client): Response => $client->delete($url, $data);
    }
}

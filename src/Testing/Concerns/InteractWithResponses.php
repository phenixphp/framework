<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Amp\Cancellation;
use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Amp\Socket\DnsSocketConnector;
use Amp\Socket\Socket;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketConnector;
use League\Uri\Uri;
use Phenix\Facades\Config;
use Phenix\Facades\Url;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Testing\TestResponse;

use function array_key_exists;
use function is_array;

trait InteractWithResponses
{
    protected int $redirectsFollowed = 0;

    protected function followingRedirects(): static
    {
        $this->redirectsFollowed = 10;

        return $this;
    }

    public function call(
        HttpMethod $method,
        string $path,
        array $parameters = [],
        Form|array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        $publicUri = Uri::new($this->isAbsoluteUri($path) ? $path : Url::to($path, $parameters));
        $uri = $this->resolveRequestUri($path, $parameters);
        $request = new Request($uri, $method->value);

        if (! array_key_exists('Host', $headers) && ! array_key_exists('host', $headers)) {
            $publicHost = $publicUri->getHost();
            $publicPort = $publicUri->getPort();

            if ($publicHost !== '') {
                $request->setHeader('Host', $publicHost . ($publicPort === null ? '' : ":{$publicPort}"));
            }
        }

        if ($headers) {
            $request->setHeaders($headers);
        }

        if ($body) {
            $body = match (true) {
                is_array($body) => json_encode($body),
                default => $body,
            };

            $request->setBody($body);
        }

        $connector = new class () implements SocketConnector {
            public function connect(
                SocketAddress|string $uri,
                ConnectContext|null $context = null,
                Cancellation|null $cancellation = null
            ): Socket {
                $context = (new ConnectContext())
                    ->withTlsContext((new ClientTlsContext(''))->withoutPeerVerification());

                return (new DnsSocketConnector())->connect($uri, $context, $cancellation);
            }
        };

        $client = (new HttpClientBuilder())
            ->followRedirects($this->redirectsFollowed)
            ->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory($connector)))
            ->build();

        return new TestResponse($client->request($request));
    }

    public function get(string $path, array $headers = []): TestResponse
    {
        return $this->call(
            method: HttpMethod::GET,
            path: $path,
            headers: $headers
        );
    }

    public function post(
        string $path,
        Form|array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        return $this->call(
            method: HttpMethod::POST,
            path: $path,
            body: $body,
            headers: $headers
        );
    }

    public function put(
        string $path,
        Form|array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        return $this->call(
            method: HttpMethod::PUT,
            path: $path,
            body: $body,
            headers: $headers
        );
    }

    public function patch(
        string $path,
        Form|array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        return $this->call(
            method: HttpMethod::PATCH,
            path: $path,
            body: $body,
            headers: $headers
        );
    }

    public function delete(string $path, array $headers = []): TestResponse
    {
        return $this->call(
            method: HttpMethod::DELETE,
            path: $path,
            headers: $headers
        );
    }

    public function options(
        string $path,
        array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        return $this->call(
            method: HttpMethod::OPTIONS,
            path: $path,
            body: $body,
            headers: $headers
        );
    }

    private function resolveRequestUri(string $path, array $parameters = []): string
    {
        if (! $this->isAbsoluteUri($path)) {
            $path = Url::to($path, $parameters);
            $parameters = [];
        }

        $uri = Uri::new($path);
        $bindHost = trim((string) Config::get('app.host', '127.0.0.1'), '[]');

        if ($bindHost === '0.0.0.0') {
            $bindHost = '127.0.0.1';
        } elseif ($bindHost === '::') {
            $bindHost = '::1';
        }

        $host = str_contains($bindHost, ':') ? "[{$bindHost}]" : $bindHost;
        $scheme = Config::get('app.cert_path') ? 'https' : 'http';
        $port = (int) Config::get('app.port', 1337);
        $requestPath = $uri->getPath() ?: '/';
        $query = $uri->getQuery() ?? '';

        if (! empty($parameters)) {
            $query .= ($query === '' ? '' : '&') . http_build_query($parameters);
        }

        return "{$scheme}://{$host}:{$port}{$requestPath}" . ($query === '' ? '' : "?{$query}");
    }

    private function isAbsoluteUri(string $path): bool
    {
        $uri = Uri::new($path);
        $scheme = $uri->getScheme();
        $host = $uri->getHost();

        return $scheme !== null && $scheme !== '' && $host !== null && $host !== '';
    }
}

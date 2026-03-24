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
use Phenix\Facades\Url;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Testing\TestResponse;

use function is_array;

trait InteractWithResponses
{
    public function call(
        HttpMethod $method,
        string $path,
        array $parameters = [],
        Form|array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        $uri = $this->resolveRequestUri($path, $parameters);
        $request = new Request($uri, $method->value);

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
            return Url::to($path, $parameters);
        }

        if (empty($parameters)) {
            return $path;
        }

        return $path . (str_contains($path, '?') ? '&' : '?') . http_build_query($parameters);
    }

    private function isAbsoluteUri(string $path): bool
    {
        $scheme = parse_url($path, PHP_URL_SCHEME);
        $host = parse_url($path, PHP_URL_HOST);

        return is_string($scheme) && $scheme !== '' && is_string($host) && $host !== '';
    }
}

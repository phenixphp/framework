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
use Phenix\Http\Constants\HttpMethod;
use Phenix\Testing\TestResponse;
use Phenix\Facades\Url;

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
        $request = new Request(Url::to($path, $parameters), $method->value);

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

    public function get(string $path, array $parameters = [], array $headers = []): TestResponse
    {
        return $this->call(
            method: HttpMethod::GET,
            path: $path,
            parameters: $parameters,
            headers: $headers
        );
    }

    public function post(
        string $path,
        Form|array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethod::POST, $path, $parameters, $body, $headers);
    }

    public function put(
        string $path,
        Form|array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethod::PUT, $path, $parameters, $body, $headers);
    }

    public function patch(
        string $path,
        Form|array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethod::PATCH, $path, $parameters, $body, $headers);
    }

    public function delete(string $path, array $parameters = [], array $headers = []): TestResponse
    {
        return $this->call(
            method: HttpMethod::DELETE,
            path: $path,
            parameters: $parameters,
            headers: $headers
        );
    }

    public function options(
        string $path,
        array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethod::OPTIONS, $path, $parameters, $body, $headers);
    }
}

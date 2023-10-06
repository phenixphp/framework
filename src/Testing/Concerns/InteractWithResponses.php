<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Phenix\Constants\HttpMethods;
use Phenix\Testing\TestResponse;
use Phenix\Util\URL;

use function is_array;

trait InteractWithResponses
{
    public function call(
        HttpMethods $method,
        string $path,
        array $parameters = [],
        array|string|null $body = null,
        array $headers = []
    ): TestResponse {
        $request = new Request(URL::build($path, $parameters), $method->value);

        if (! empty($headers)) {
            $request->setHeaders($headers);
        }

        if (! empty($body)) {
            $body = is_array($body) ? json_encode($body) : $body;

            $request->setBody($body);
        }

        $client = HttpClientBuilder::buildDefault();

        return new TestResponse($client->request($request));
    }

    public function get(string $path, array $parameters = [], array $headers = []): TestResponse
    {
        return $this->call(
            method: HttpMethods::GET,
            path: $path,
            parameters: $parameters,
            headers: $headers
        );
    }

    public function post(
        string $path,
        array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethods::POST, $path, $parameters, $body, $headers);
    }

    public function put(
        string $path,
        array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethods::PUT, $path, $parameters, $body, $headers);
    }

    public function patch(
        string $path,
        array|string|null $body = null,
        array $parameters = [],
        array $headers = []
    ): TestResponse {
        return $this->call(HttpMethods::PATCH, $path, $parameters, $body, $headers);
    }

    public function delete(string $path, array $parameters = [], array $headers = []): TestResponse
    {
        return $this->call(
            method: HttpMethods::DELETE,
            path: $path,
            parameters: $parameters,
            headers: $headers
        );
    }
}

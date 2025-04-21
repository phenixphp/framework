<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Amp\Http\Client\Form;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Testing\TestResponse;
use Phenix\Util\URL;

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
        $request = new Request(URL::build($path, $parameters), $method->value);

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

        $client = HttpClientBuilder::buildDefault();

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

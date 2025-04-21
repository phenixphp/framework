<?php

declare(strict_types=1);

namespace Phenix\Routing;

use Amp\Http\Server\Middleware;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Contracts\Arrayable;
use Phenix\Http\Requests\ClosureRequestHandler;

class RouteBuilder implements Arrayable
{
    protected string|null $baseName = null;

    protected string $name = '';

    /**
     * @var array<int, string>
     */
    protected array $parameters = [];

    /**
     * @var array<int, \Amp\Http\Server\Middleware|string|null>
     */
    protected array $middlewares = [];

    public function __construct(
        protected HttpMethod $method,
        protected string $path,
        protected ClosureRequestHandler $closure,
        string|null $name = null,
        array $middleware = [],
    ) {
        $this->parameters = $this->extractParams($path);
        $this->baseName = $name;
        $this->middleware($middleware);
    }

    public function name(string $name): self
    {
        $this->name = $this->baseName . trim($name, '.');

        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        foreach ((array) $middleware as $item) {
            $this->pushMiddleware(new $item());
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            $this->method,
            '/' . trim($this->path, '/'),
            $this->closure,
            $this->middlewares,
            trim($this->name, '.'),
            $this->parameters,
        ];
    }

    protected function extractParams(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $params);

        return array_unique($params[1]);
    }

    protected function pushMiddleware(Middleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }
}

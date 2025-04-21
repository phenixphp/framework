<?php

declare(strict_types=1);

namespace Phenix\Routing;

use Closure;
use Phenix\App;
use Phenix\Contracts\Arrayable;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Http\FormRequest;
use Phenix\Http\Request;
use Phenix\Http\Requests\ClosureRequestHandler;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

use function is_array;

class Route implements Arrayable
{
    private array $collection;

    public function __construct(
        private string $baseName = '',
        private string $prefix = '',
        private array $middleware = [],
    ) {
        $this->collection = [];
    }

    /**
     * @param Closure|array<int, string> $handler
     */
    public function get(string $path, Closure|array $handler): RouteBuilder
    {
        return $this->addRoute(HttpMethod::GET, $path, $handler);
    }

    public function post(string $path, Closure|array $handler): RouteBuilder
    {
        return $this->addRoute(HttpMethod::POST, $path, $handler);
    }

    public function put(string $path, Closure|array $handler): RouteBuilder
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handler);
    }

    public function patch(string $path, Closure|array $handler): RouteBuilder
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handler);
    }

    public function delete(string $path, Closure|array $handler): RouteBuilder
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handler);
    }

    public function group(Closure $closure): RouteGroupBuilder
    {
        $group = new RouteGroupBuilder($this->prefix, $this->baseName, $this->middleware);
        $group->group($closure);

        $this->collection[] = $group;

        return $group;
    }

    public function name(string $name): RouteGroupBuilder
    {
        $group = new RouteGroupBuilder($this->prefix, $this->baseName, $this->middleware);
        $group->name($name);

        $this->collection[] = $group;

        return $group;
    }

    public function prefix(string $prefix): RouteGroupBuilder
    {
        $group = new RouteGroupBuilder($this->prefix, $this->baseName, $this->middleware);
        $group->prefix($prefix);

        $this->collection[] = $group;

        return $group;
    }

    public function middleware(array|string $middleware): RouteGroupBuilder
    {
        $group = new RouteGroupBuilder($this->prefix, $this->baseName, $this->middleware);
        $group->middleware($middleware);

        $this->collection[] = $group;

        return $group;
    }

    /**
     * @return array<int, array>
     */
    public function toArray(): array
    {
        return array_reduce($this->collection, function (array $routes, Arrayable $item) {
            if ($item instanceof RouteGroupBuilder) {
                return array_merge($routes, $item->toArray());
            }

            array_push($routes, $item->toArray());

            return $routes;
        }, []);
    }

    private function addRoute(HttpMethod $method, string $path, Closure|array $handler): RouteBuilder
    {
        $route = new RouteBuilder(
            $method,
            $this->prefix . $path,
            $this->callable($handler),
            $this->baseName,
            $this->middleware
        );

        $this->collection[] = $route;

        return $route;
    }

    /**
     * @param array<int, string> $handler
     */
    private function callable(Closure|array $handler): ClosureRequestHandler
    {
        if (is_array($handler)) {
            [$controller, $method] = $handler;

            $controller = App::make($controller);

            $handler = $controller->{$method}(...);
        }

        return new ClosureRequestHandler($handler, $this->resolveClosureParam($handler));
    }

    private function resolveClosureParam(Closure $closure): string
    {
        $reflector = new ReflectionFunction($closure);

        /** @var ReflectionParameter|null $parameter */
        $parameter = $reflector->getParameters()[0] ?? null;

        if (! $parameter) {
            return Request::class;
        }

        /** @var ReflectionNamedType|null $type */
        $type = $parameter->getType();
        $className = $type?->getName();

        return ($className && is_subclass_of($className, FormRequest::class)) ? $className : Request::class;
    }
}

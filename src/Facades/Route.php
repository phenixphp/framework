<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;

/**
 * @method static \Phenix\Routing\RouteBuilder get(string $path, \Closure|array $handler)
 * @method static \Phenix\Routing\RouteBuilder post(string $path, \Closure|array $handler)
 * @method static \Phenix\Routing\RouteBuilder put(string $path, \Closure|array $handler)
 * @method static \Phenix\Routing\RouteBuilder patch(string $path, \Closure|array $handler)
 * @method static \Phenix\Routing\RouteBuilder delete(string $path, \Closure|array $handler)
 * @method static \Phenix\Routing\RouteGroupBuilder group(\Closure $closure)
 * @method static \Phenix\Routing\RouteGroupBuilder name(string $name)
 * @method static \Phenix\Routing\RouteGroupBuilder prefix(string $prefix)
 * @method static \Phenix\Routing\RouteGroupBuilder middleware(array|string $middleware)
 */
class Route extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Routing\Route::class;
    }
}

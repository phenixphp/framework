<?php

declare(strict_types=1);

namespace Phenix\Routing;

use Amp\Http\Server\Request;
use BackedEnum;
use Closure;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Phenix\Crypto\Bin2Base64;
use Phenix\Facades\Config;
use Phenix\Routing\Exceptions\RouteNotFoundException;

use function array_key_exists;

class UrlGenerator
{
    protected Route $routes;

    protected string $key;

    public function __construct(Route $routes)
    {
        $this->routes = $routes;
        $this->key = Bin2Base64::decode(Config::get('app.key'));
    }

    public function route(BackedEnum|string $name, array $parameters = [], bool $absolute = true): string
    {
        $name = $name instanceof BackedEnum ? $name->value : $name;

        $path = $this->resolveRouteByName($name);
        $path = $this->substituteParameters($path, $parameters);

        if ($absolute) {
            return $this->buildAbsoluteUrl($path, $parameters);
        }

        $uri = '/' . ltrim($path, '/');

        if (! empty($parameters)) {
            $uri .= '?' . http_build_query($parameters);
        }

        return $uri;
    }

    public function secure(string $path, array $parameters = []): string
    {
        $path = trim($path, '/');
        $port = Config::get('app.port');

        $url = Config::get('app.url');
        $url = (string) preg_replace('/^http:/', 'https:', $url);

        $uri = "{$url}:{$port}/{$path}";

        if (! empty($parameters)) {
            $uri .= '?' . http_build_query($parameters);
        }

        return $uri;
    }

    public function signedRoute(
        BackedEnum|string $name,
        array $parameters = [],
        DateTimeInterface|DateInterval|int|null $expiration = null,
        bool $absolute = true,
    ): string {
        if ($expiration !== null) {
            $parameters['expires'] = $this->resolveExpiration($expiration);
        }

        $url = $this->route($name, $parameters, $absolute);

        $signature = hash_hmac('sha256', $url, $this->key);

        return $url . (str_contains($url, '?') ? '&' : '?') . 'signature=' . $signature;
    }

    public function temporarySignedRoute(
        BackedEnum|string $name,
        DateTimeInterface|DateInterval|int $expiration,
        array $parameters = [],
        bool $absolute = true,
    ): string {
        return $this->signedRoute($name, $parameters, $expiration, $absolute);
    }

    public function hasValidSignature(Request $request, bool $absolute = true, Closure|array $ignoreQuery = []): bool
    {
        $signature = $request->getQueryParameter('signature');

        if ($signature === null) {
            return false;
        }

        $url = $this->rebuildRequestUrl($request, $absolute, $ignoreQuery);

        $expected = hash_hmac('sha256', $url, $this->key);

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        return $this->signatureHasNotExpired($request);
    }

    /**
     * Determine if the signature of the given request has not expired.
     */
    public function signatureHasNotExpired(Request $request): bool
    {
        $expires = $request->getQueryParameter('expires');

        if ($expires === null) {
            return true;
        }

        return (int) $expires > time();
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function resolveRouteByName(string $name): string
    {
        $routes = $this->routes->toArray();

        foreach ($routes as $route) {
            if (($route[4] ?? '') === $name) {
                return $route[1];
            }
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }

    /**
     * Substitute route parameters in the path and remove used parameters.
     */
    protected function substituteParameters(string $path, array &$parameters): string
    {
        return (string) preg_replace_callback('/\{(\w+)\}/', function (array $matches) use (&$parameters): string {
            $key = $matches[1];

            if (array_key_exists($key, $parameters)) {
                $value = (string) $parameters[$key];
                unset($parameters[$key]);

                return $value;
            }

            return $matches[0];
        }, $path);
    }

    /**
     * Build an absolute URL from a path and query parameters.
     */
    protected function buildAbsoluteUrl(string $path, array $parameters = []): string
    {
        $path = trim($path, '/');
        $port = Config::get('app.port');
        $url = Config::get('app.url');

        $uri = "{$url}:{$port}/{$path}";

        if (! empty($parameters)) {
            $uri .= '?' . http_build_query($parameters);
        }

        return $uri;
    }

    /**
     * Resolve an expiration value to a UNIX timestamp.
     */
    protected function resolveExpiration(DateTimeInterface|DateInterval|int $expiration): int
    {
        if ($expiration instanceof DateTimeInterface) {
            return $expiration->getTimestamp();
        }

        if ($expiration instanceof DateInterval) {
            return (new DateTimeImmutable())->add($expiration)->getTimestamp();
        }

        return time() + $expiration;
    }

    /**
     * Rebuild the URL from the request, excluding the signature and
     * any parameters specified in the ignore list.
     */
    protected function rebuildRequestUrl(Request $request, bool $absolute, Closure|array $ignoreQuery): string
    {
        $ignoredParams = $ignoreQuery instanceof Closure ? $ignoreQuery() : $ignoreQuery;
        $ignoredParams[] = 'signature';

        $uri = $request->getUri();

        $queryParams = [];

        parse_str($uri->getQuery(), $queryParams);

        foreach ($ignoredParams as $param) {
            unset($queryParams[$param]);
        }

        $path = $uri->getPath();

        if (! $absolute) {
            $rebuilt = $path;
        } else {
            $scheme = $uri->getScheme();
            $host = $uri->getHost();
            $port = $uri->getPort();

            $rebuilt = "{$scheme}://{$host}";

            if ($port !== null) {
                $rebuilt .= ":{$port}";
            }

            $rebuilt .= $path;
        }

        if (! empty($queryParams)) {
            $rebuilt .= '?' . http_build_query($queryParams);
        }

        return $rebuilt;
    }
}

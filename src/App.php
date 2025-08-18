<?php

declare(strict_types=1);

namespace Phenix;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use League\Container\Container;
use League\Uri\Uri;
use Monolog\Logger;
use Phenix\Console\Phenix;
use Phenix\Contracts\App as AppContract;
use Phenix\Contracts\Makeable;
use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Phenix\Logging\LoggerFactory;
use Phenix\Runtime\Log;
use Phenix\Session\SessionMiddleware;

class App implements AppContract, Makeable
{
    private static string $path;

    private static Container $container;

    private string $host;

    private RequestHandler $router;

    private Logger $logger;

    private SocketHttpServer $server;

    private bool $signalTrapping = true;

    private DefaultErrorHandler $errorHandler;

    public function __construct(string $path)
    {
        self::$path = $path;
        self::$container = new Container();

        $this->errorHandler = new DefaultErrorHandler();
    }

    public function setup(): void
    {
        self::$container->add(
            Config::getKeyName(),
            \Phenix\Runtime\Config::build(...)
        )->setShared(true);

        $this->host = $this->getHost();

        /** @var array $providers */
        $providers = Config::get('app.providers', []);

        foreach ($providers as $provider) {
            self::$container->addServiceProvider(new $provider());
        }

        /** @var string $channel */
        $channel = Config::get('logging.default', 'file');

        $this->logger = LoggerFactory::make($channel);

        self::$container->add(Phenix::class)->addMethodCall('registerCommands');

        $this->register(Log::class, new Log($this->logger));
    }

    public function run(): void
    {
        $this->server = SocketHttpServer::createForDirectAccess($this->logger);

        $this->setRouter();

        $port = $this->getPort();

        $this->server->expose(new Socket\InternetAddress($this->host, $port));

        $this->server->start($this->router, $this->errorHandler);

        if ($this->signalTrapping) {
            $signal = \Amp\trapSignal([SIGINT, SIGTERM]);

            $this->logger->info("Caught signal {$signal}, stopping server");

            $this->stop();
        }
    }

    public function stop(): void
    {
        $this->server->stop();
    }

    public static function make(string $key): object
    {
        return self::$container->get($key);
    }

    public static function path(): string
    {
        return self::$path;
    }

    public function swap(string $key, object $concrete): void
    {
        self::$container->extend($key)->setConcrete($concrete);
    }

    public function register(string $key, mixed $concrete = null)
    {
        self::$container->add($key, $concrete);
    }

    public function disableSignalTrapping(): void
    {
        $this->signalTrapping = false;
    }

    private function setRouter(): void
    {
        $router = new Router($this->server, $this->logger, $this->errorHandler);

        /** @var array $routes */
        $routes = self::$container->get(Route::getKeyName())->toArray();

        foreach ($routes as $route) {
            [$method, $path, $closure, $middlewares] = $route;

            $router->addRoute(
                $method->value,
                $path,
                Middleware\stackMiddleware($closure, ...$middlewares)
            );
        }

        /** @var array $middlewares */
        $middlewares = Config::get('app.middlewares');

        foreach ($middlewares['router'] as $middleware) {
            $router->addMiddleware(new $middleware());
        }

        /** @var array<int, Middleware> $globalMiddlewares */
        $globalMiddlewares = array_map(fn (string $middleware) => new $middleware(), $middlewares['global']);

        $globalMiddlewares[] = SessionMiddleware::make($this->host);

        $this->router = Middleware\stackMiddleware($router, ...$globalMiddlewares);
    }

    private function getHost(): string
    {
        $host = $this->getHostFromOptions() ?? Uri::new(Config::get('app.url'))->getHost();

        return $host;
    }

    private function getPort(): int
    {
        $port = $this->getPortFromOptions() ?? Config::get('app.port');

        return (int) $port;
    }

    private function getHostFromOptions(): string|null
    {
        $options = getopt('', ['host:']);

        return $options['host'] ?? null;
    }

    private function getPortFromOptions(): string|null
    {
        $options = getopt('', ['port:']);

        return $options['port'] ?? null;
    }
}

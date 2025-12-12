<?php

declare(strict_types=1);

namespace Phenix;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Middleware\ForwardedHeaderType;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use League\Container\Container;
use League\Uri\Uri;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Monolog\Logger;
use Phenix\Console\Phenix;
use Phenix\Constants\AppMode;
use Phenix\Contracts\App as AppContract;
use Phenix\Contracts\Makeable;
use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Phenix\Logging\LoggerFactory;
use Phenix\Runtime\Log;
use Phenix\Session\SessionMiddlewareFactory;

use function Amp\trapSignal;
use function count;
use function is_array;

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

        self::$container->add(Phenix::class)->addMethodCall('registerCommands');

        /** @var array $providers */
        $providers = Config::get('app.providers', []);

        foreach ($providers as $provider) {
            self::$container->addServiceProvider(new $provider());
        }

        /** @var string $channel */
        $channel = Config::get('logging.default', 'file');

        $this->logger = LoggerFactory::make($channel);

        $this->register(Log::class, new Log($this->logger));
    }

    public function run(): void
    {
        $this->server = $this->createServer();

        $this->setRouter();

        $port = $this->getPort();

        $this->server->expose(new Socket\InternetAddress($this->host, $port));

        $this->server->start($this->router, $this->errorHandler);

        if ($this->signalTrapping) {
            $signal = trapSignal([SIGHUP, SIGINT, SIGQUIT, SIGTERM]);

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

    public static function fake(string $key, LegacyMockInterface|MockInterface $concrete): void
    {
        self::$container->extend($key)->setConcrete($concrete);
    }

    public static function path(): string
    {
        return self::$path;
    }

    public static function isLocal(): bool
    {
        return Config::get('app.env') === 'local';
    }

    public static function isProduction(): bool
    {
        return Config::get('app.env') === 'production';
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

    protected function setRouter(): void
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

        $globalMiddlewares[] = SessionMiddlewareFactory::make($this->host);

        $this->router = Middleware\stackMiddleware($router, ...$globalMiddlewares);
    }

    protected function getHost(): string
    {
        return $this->getHostFromOptions() ?? Uri::new(Config::get('app.url'))->getHost();
    }

    protected function getPort(): int
    {
        $port = $this->getPortFromOptions() ?? Config::get('app.port');

        return (int) $port;
    }

    protected function createServer(): SocketHttpServer
    {
        $mode = AppMode::tryFrom(Config::get('app.app_mode', AppMode::DIRECT->value)) ?? AppMode::DIRECT;

        if ($mode === AppMode::PROXIED) {
            /** @var array<int, string> $trustedProxies */
            $trustedProxies = Config::get('app.trusted_proxies', []);

            assert(is_array($trustedProxies) && count($trustedProxies) >= 0);

            return SocketHttpServer::createForBehindProxy(
                $this->logger,
                ForwardedHeaderType::XForwardedFor,
                $trustedProxies
            );
        }

        return SocketHttpServer::createForDirectAccess($this->logger);
    }

    protected function getHostFromOptions(): string|null
    {
        $options = getopt('', ['host:']);

        return $options['host'] ?? null;
    }

    protected function getPortFromOptions(): string|null
    {
        $options = getopt('', ['port:']);

        return $options['port'] ?? null;
    }
}

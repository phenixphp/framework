<?php

declare(strict_types=1);

namespace Phenix;

use Amp\Cluster\Cluster;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Middleware\CompressionMiddleware;
use Amp\Http\Server\Middleware\ForwardedHeaderType;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\ServerTlsContext;
use Amp\Sync\LocalSemaphore;
use League\Container\Container;
use League\Uri\Uri;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Monolog\Logger;
use Phenix\Console\Phenix;
use Phenix\Constants\AppMode;
use Phenix\Constants\ServerMode;
use Phenix\Contracts\App as AppContract;
use Phenix\Contracts\Makeable;
use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\Config;
use Phenix\Facades\Route;
use Phenix\Http\Constants\Protocol;
use Phenix\Logging\LoggerFactory;
use Phenix\Runtime\Log;
use Phenix\Scheduling\TimerRegistry;
use Phenix\Session\SessionMiddlewareFactory;

use function Amp\async;
use function Amp\trapSignal;
use function count;
use function extension_loaded;
use function is_array;

class App implements AppContract, Makeable
{
    protected static string $path;

    protected static Container $container;

    protected string $host;

    protected RequestHandler $router;

    protected Logger $logger;

    protected SocketHttpServer $server;

    protected bool $signalTrapping = true;

    protected DefaultErrorHandler $errorHandler;

    protected Protocol $protocol = Protocol::HTTP;

    protected AppMode $appMode;

    protected ServerMode $serverMode;

    protected bool $isRunning = false;

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

        self::$container->add(Phenix::class)->addMethodCall('registerCommands');

        /** @var array $providers */
        $providers = Config::get('app.providers', []);

        foreach ($providers as $provider) {
            self::$container->addServiceProvider(new $provider());
        }

        $this->serverMode = ServerMode::tryFrom(Config::get('app.server_mode', ServerMode::SINGLE->value)) ?? ServerMode::SINGLE;

        $this->setLogger();
    }

    public function run(): void
    {
        $this->appMode = AppMode::tryFrom(Config::get('app.app_mode', AppMode::DIRECT->value)) ?? AppMode::DIRECT;

        $this->detectProtocol();

        $this->host = Uri::new(Config::get('app.url'))->getHost();

        $this->server = $this->createServer();

        $this->setRouter();

        $this->expose();

        $this->server->start($this->router, $this->errorHandler);

        $this->isRunning = true;

        TimerRegistry::run();

        if ($this->serverMode === ServerMode::CLUSTER && $this->signalTrapping) {
            async(function (): void {
                Cluster::awaitTermination();

                $this->logger->info('Received termination request');

                $this->stop();
            });
        } elseif ($this->signalTrapping) {
            $signal = trapSignal([SIGHUP, SIGINT, SIGQUIT, SIGTERM]);

            $this->logger->info("Caught signal {$signal}, stopping server");

            $this->stop();
        }
    }

    public function stop(): void
    {
        if ($this->isRunning) {
            $this->server->stop();

            $this->isRunning = false;
        }
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

    protected function setLogger(): void
    {
        /** @var string $channel */
        $channel = Config::get('logging.default', 'file');

        $this->logger = LoggerFactory::make($channel, $this->serverMode);

        $this->register(Log::class, new Log($this->logger));
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

    protected function createServer(): SocketHttpServer
    {
        if ($this->serverMode === ServerMode::CLUSTER) {
            return $this->createClusterServer();
        }

        if ($this->appMode === AppMode::PROXIED) {
            /** @var array<int, string> $trustedProxies */
            $trustedProxies = Config::get('app.trusted_proxies', []);

            if (is_array($trustedProxies) && count($trustedProxies) === 0) {
                throw new RuntimeError('Trusted proxies must be an array of IP addresses or CIDRs.');
            }

            return SocketHttpServer::createForBehindProxy(
                $this->logger,
                ForwardedHeaderType::XForwardedFor,
                $trustedProxies
            );
        }

        return SocketHttpServer::createForDirectAccess($this->logger);
    }

    protected function createClusterServer(): SocketHttpServer
    {
        $middleware = [];
        $allowedMethods = Middleware\AllowedMethodsMiddleware::DEFAULT_ALLOWED_METHODS;

        if (extension_loaded('zlib')) {
            $middleware[] = new CompressionMiddleware();
        }

        if ($this->appMode === AppMode::PROXIED) {
            /** @var array<int, string> $trustedProxies */
            $trustedProxies = Config::get('app.trusted_proxies', []);

            if (is_array($trustedProxies) && count($trustedProxies) === 0) {
                throw new RuntimeError('Trusted proxies must be an array of IP addresses or CIDRs.');
            }

            $middleware[] = new Middleware\ForwardedMiddleware(ForwardedHeaderType::XForwardedFor, $trustedProxies);

            return new SocketHttpServer(
                $this->logger,
                Cluster::getServerSocketFactory(),
                new SocketClientFactory($this->logger),
                $middleware,
                $allowedMethods,
            );
        }

        $connectionLimit = 1000;
        $connectionLimitPerIp = 10;

        $serverSocketFactory = new ConnectionLimitingServerSocketFactory(
            new LocalSemaphore($connectionLimit),
            Cluster::getServerSocketFactory(),
        );

        $clientFactory = new ConnectionLimitingClientFactory(
            new SocketClientFactory($this->logger),
            $this->logger,
            $connectionLimitPerIp,
        );

        return new SocketHttpServer(
            $this->logger,
            $serverSocketFactory,
            $clientFactory,
            $middleware,
            $allowedMethods,
        );
    }

    protected function expose(): void
    {
        $port = (int) Config::get('app.port');
        $plainBindContext = (new BindContext())->withTcpNoDelay();

        if ($this->protocol === Protocol::HTTPS) {
            /** @var string|null $certPath */
            $certPath = Config::get('app.cert_path');

            $tlsBindContext = $plainBindContext->withTlsContext(
                (new ServerTlsContext())->withDefaultCertificate(new Certificate($certPath))
            );

            $this->server->expose("{$this->host}:{$port}", $tlsBindContext);

            return;
        }

        $this->server->expose("{$this->host}:{$port}", $plainBindContext);
    }

    protected function detectProtocol(): void
    {
        $url = (string) Config::get('app.url');

        /** @var string|null $certPath */
        $certPath = Config::get('app.cert_path');

        $this->protocol = str_starts_with($url, 'https://') && $certPath !== null ? Protocol::HTTPS : Protocol::HTTP;
    }
}

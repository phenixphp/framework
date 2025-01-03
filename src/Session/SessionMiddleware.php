<?php

declare(strict_types=1);

namespace Phenix\Session;

use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\RedisSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware as Middleware;
use Phenix\App;
use Phenix\Database\Constants\Connection;
use Phenix\Session\Constants\Driver;

class SessionMiddleware
{
    public static function make(string $host): Middleware
    {
        $config = new Config();
        $cookie = new Cookie($config, $host);

        $driver = $config->driver();

        $storage = new LocalSessionStorage();

        if ($driver === Driver::REDIS) {
            $connection = Connection::redis($config->connection());

            $client = App::make($connection);
            $storage = new RedisSessionStorage($client);
        }

        $factory = new SessionFactory(storage: $storage);

        return new Middleware(
            factory: $factory,
            cookieAttributes: $cookie->build(),
            cookieName: $config->cookieName(),
        );
    }
}

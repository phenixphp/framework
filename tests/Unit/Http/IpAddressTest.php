<?php

declare(strict_types=1);

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware\Forwarded;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Socket\InternetAddress;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketAddressType;
use League\Uri\Http;
use Phenix\Constants\AppMode;
use Phenix\Facades\Config;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Http\Ip;
use Phenix\Util\URL;

it('generate ip hash from request', function (string $ip, $expected): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ($ip) implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $uri = Http::new(URL::build('posts/7/comments/22'));
    $request = new ServerRequest($client, HttpMethod::GET->value, $uri);

    $ip = Ip::make($request);

    expect($ip->hash())->toBe($expected);
    expect($ip->isForwarded())->toBeFalse();
    expect($ip->forwardingAddress())->toBeNull();
})->with([
    ['192.168.1.1', hash('sha256', '192.168.1.1')],
    ['192.168.1.1:8080', hash('sha256', '192.168.1.1')],
    ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', hash('sha256', '2001:0db8:85a3:0000:0000:8a2e:0370:7334')],
    ['fe80::1ff:fe23:4567:890a', hash('sha256', 'fe80::1ff:fe23:4567:890a')],
    ['[2001:db8::1]:443', hash('sha256', '2001:db8::1')],
    ['::1', hash('sha256', '::1')],
    ['2001:db8::7334', hash('sha256', '2001:db8::7334')],
    ['203.0.113.1', hash('sha256', '203.0.113.1')],
    [' 192.168.0.1:8080', hash('sha256', '192.168.0.1')],
    ['::ffff:192.168.0.1', hash('sha256', '::ffff:192.168.0.1')],
]);

it('parses host and port from remote address IPv6 bracket with port', function (): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ('[2001:db8::1]:443') implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $request = new ServerRequest($client, HttpMethod::GET->value, Http::new(URL::build('/')));
    $ip = Ip::make($request);

    expect($ip->address())->toBe('[2001:db8::1]:443');
    expect($ip->host())->toBe('2001:db8::1');
    expect($ip->port())->toBe(443);
    expect($ip->isForwarded())->toBeFalse();
    expect($ip->forwardingAddress())->toBeNull();
});

it('parses host only from raw IPv6 without port', function (): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ('2001:db8::2') implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $request = new ServerRequest($client, HttpMethod::GET->value, Http::new(URL::build('/')));
    $ip = Ip::make($request);

    expect($ip->host())->toBe('2001:db8::2');
    expect($ip->port())->toBeNull();
});

it('parses host and port from IPv4 with port', function (): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ('192.168.0.1:8080') implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $request = new ServerRequest($client, HttpMethod::GET->value, Http::new(URL::build('/')));
    $ip = Ip::make($request);

    expect($ip->host())->toBe('192.168.0.1');
    expect($ip->port())->toBe(8080);
});

it('parses host only from hostname with port', function (): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ('localhost:3000') implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $request = new ServerRequest($client, HttpMethod::GET->value, Http::new(URL::build('/')));
    $ip = Ip::make($request);

    expect($ip->host())->toBe('localhost');
    expect($ip->port())->toBe(3000);
});

it('parses host only from hostname without port', function (): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ('example.com') implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $request = new ServerRequest($client, HttpMethod::GET->value, Http::new(URL::build('/')));
    $ip = Ip::make($request);

    expect($ip->host())->toBe('example.com');
    expect($ip->port())->toBeNull();
});

it('sets forwarding info from X-Forwarded-For header', function (): void {
    $client = $this->createMock(Client::class);
    $client->method('getRemoteAddress')->willReturn(
        new class ('10.0.0.1:1234') implements SocketAddress {
            public function __construct(private string $address)
            {
            }

            public function toString(): string
            {
                return $this->address;
            }

            public function getType(): SocketAddressType
            {
                return SocketAddressType::Internet;
            }

            public function __toString(): string
            {
                return $this->address;
            }
        }
    );

    $request = new ServerRequest($client, HttpMethod::GET->value, Http::new(URL::build('/')));
    $request->setHeader('X-Forwarded-For', '203.0.113.1');
    $request->setAttribute(
        Forwarded::class,
        new Forwarded(
            new InternetAddress('203.0.113.1', 4711),
            [
                'for' => '203.0.113.1:4711',
            ]
        )
    );

    $ip = Ip::make($request);

    expect($ip->isForwarded())->toBeTrue();
    expect($ip->forwardingAddress())->toBe('203.0.113.1:4711');
});

<?php

declare(strict_types=1);

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Socket\SocketAddress;
use Amp\Socket\SocketAddressType;
use League\Uri\Http;
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
    expect($ip->forwardingAddresses())->toBe([]);
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

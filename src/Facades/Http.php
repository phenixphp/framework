<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Phenix\App;
use Phenix\Http\Client\HttpClient;
use Phenix\Runtime\Facade;
use Phenix\Testing\Mockery;

/**
 * @method static \Phenix\Http\Client\Response get(\Psr\Http\Message\UriInterface|string $url, array|null $queryParameters = null)
 * @method static \Phenix\Http\Client\Response head(\Psr\Http\Message\UriInterface|string $url, array|null $queryParameters = null)
 * @method static \Phenix\Http\Client\Response post(\Psr\Http\Message\UriInterface|string $url, \Amp\Http\Client\Form|\Phenix\Contracts\Arrayable|array|string $data = [])
 * @method static \Phenix\Http\Client\Response put(\Psr\Http\Message\UriInterface|string $url, \Amp\Http\Client\Form|\Phenix\Contracts\Arrayable|array|string $data = [])
 * @method static \Phenix\Http\Client\Response patch(\Psr\Http\Message\UriInterface|string $url, \Amp\Http\Client\Form|\Phenix\Contracts\Arrayable|array|string $data = [])
 * @method static \Phenix\Http\Client\Response delete(\Psr\Http\Message\UriInterface|string $url, \Amp\Http\Client\Form|\Phenix\Contracts\Arrayable|array|string $data = [])
 * @method static mixed stream(\Psr\Http\Message\UriInterface|string $url, \Closure|null $callback = null, array|null $queryParameters = null, int|null $bodySizeLimit = null, float|null $transferTimeout = null, \Phenix\Http\Constants\HttpMethod $method = \Phenix\Http\Constants\HttpMethod::GET, \Amp\Http\Client\Form|\Phenix\Contracts\Arrayable|array|string|null $data = null)
 * @method static \Phenix\Http\Client\HttpClient withHeaders(array $headers)
 * @method static \Phenix\Http\Client\HttpClient withClient(\Amp\Http\Client\HttpClient $client)
 * @method static \Phenix\Http\Client\HttpClient withBasicAuth(string $username, string $password)
 * @method static \Phenix\Http\Client\HttpClient withToken(string $token, string $type = 'Bearer')
 * @method static \Phenix\Http\Client\HttpClient retry(int $times, \Closure|int $sleepMilliseconds = 0, callable|null $when = null)
 * @method static \Phenix\Http\Client\HttpClient listen(\Amp\Http\Client\EventListener $eventListener)
 * @method static \Phenix\Http\Client\HttpClient log(string $path)
 * @method static \Phenix\Http\Client\HttpClient withTlsContext(\Amp\Socket\ClientTlsContext $tlsContext)
 * @method static \Phenix\Http\Client\HttpClient withCertificate(string $certificate, string|null $key = null, string|null $ca = null, string|null $passphrase = null, string $peerName = '')
 * @method static \Phenix\Http\Client\HttpClient fake(\Closure|null $response = null)
 * @method static \Phenix\Http\Client\HttpClient fakeWhen(\Closure $condition, \Closure $response)
 *
 * @see \Phenix\Http\Client\HttpClient
 */
class Http extends Facade
{
    public static function getKeyName(): string
    {
        return HttpClient::class;
    }

    public static function expect(string $method): Expectation|ExpectationInterface|HigherOrderMessage
    {
        $mock = Mockery::mock(self::getKeyName())->shouldAllowMockingProtectedMethods()->makePartial();

        App::fake(self::getKeyName(), $mock);

        return $mock->shouldReceive($method);
    }
}

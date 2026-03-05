<?php

declare(strict_types=1);

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use League\Uri\Http;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Route as RouteFacade;
use Phenix\Http\Response;
use Phenix\Routing\Exceptions\RouteNotFoundException;
use Phenix\Routing\Route;
use Phenix\Routing\UrlGenerator;

beforeEach(function (): void {
    $this->key = Crypto::generateEncodedKey();
    Config::set('app.key', $this->key);
    Config::set('app.url', 'http://127.0.0.1');
    Config::set('app.port', 1337);
});

function createRequest(string $url): Request
{
    $client = new class () implements Client {
        public function getId(): int
        {
            return 1;
        }

        public function close(): void
        {
        }

        public function isClosed(): bool
        {
            return false;
        }

        public function onClose(\Closure $onClose): void
        {
        }

        public function isEncrypted(): bool
        {
            return false;
        }

        public function getRemoteAddress(): \Amp\Socket\SocketAddress
        {
            return \Amp\Socket\SocketAddress\fromString('127.0.0.1:0');
        }

        public function getLocalAddress(): \Amp\Socket\SocketAddress
        {
            return \Amp\Socket\SocketAddress\fromString('127.0.0.1:0');
        }

        public function getTlsInfo(): ?\Amp\Socket\TlsInfo
        {
            return null;
        }
    };

    return new Request($client, 'GET', Http::new($url));
}

it('generates a URL for a named route', function (): void {
    $route = new Route();
    $route->get('/users', fn (): Response => response()->plain('Ok'))
        ->name('users.index');

    $generator = new UrlGenerator($route);

    $url = $generator->route('users.index');

    expect($url)->toBe('http://127.0.0.1:1337/users');
});

it('generates a URL for a named route using helper', function (): void {
    RouteFacade::get('/users', fn (): Response => response()->plain('Ok'))
        ->name('users.index');

    $url = route('users.index');

    expect($url)->toBe('http://127.0.0.1:1337/users');
});

it('generates a URL with parameter substitution', function (): void {
    $route = new Route();
    $route->get('/users/{user}', fn (): Response => response()->plain('Ok'))
        ->name('users.show');

    $generator = new UrlGenerator($route);

    $url = $generator->route('users.show', ['user' => 42]);

    expect($url)->toBe('http://127.0.0.1:1337/users/42');
});

it('generates a URL with multiple parameter substitution', function (): void {
    $route = new Route();
    $route->get('/users/{user}/posts/{post}', fn (): Response => response()->plain('Ok'))
        ->name('users.posts.show');

    $generator = new UrlGenerator($route);

    $url = $generator->route('users.posts.show', ['user' => 5, 'post' => 10]);

    expect($url)->toBe('http://127.0.0.1:1337/users/5/posts/10');
});

it('appends extra parameters as query string', function (): void {
    $route = new Route();
    $route->get('/users/{user}', fn (): Response => response()->plain('Ok'))
        ->name('users.show');

    $generator = new UrlGenerator($route);

    $url = $generator->route('users.show', ['user' => 42, 'page' => 2]);

    expect($url)->toBe('http://127.0.0.1:1337/users/42?page=2');
});

it('generates a relative URL when absolute is false', function (): void {
    $route = new Route();
    $route->get('/users/{user}', fn (): Response => response()->plain('Ok'))
        ->name('users.show');

    $generator = new UrlGenerator($route);

    $url = $generator->route('users.show', ['user' => 42], absolute: false);

    expect($url)->toBe('/users/42');
});

it('generates a relative URL with query parameters', function (): void {
    $route = new Route();
    $route->get('/users', fn (): Response => response()->plain('Ok'))
        ->name('users.index');

    $generator = new UrlGenerator($route);

    $url = $generator->route('users.index', ['page' => 3], absolute: false);

    expect($url)->toBe('/users?page=3');
});

it('throws exception for unknown route name', function (): void {
    $route = new Route();
    $generator = new UrlGenerator($route);

    $generator->route('nonexistent');
})->throws(RouteNotFoundException::class, 'Route [nonexistent] not defined.');

it('generates HTTP URL', function (): void {
    $route = new Route();
    $generator = new UrlGenerator($route);

    $url = $generator->to('/dashboard', ['tab' => 'settings']);

    expect($url)->toStartWith('http://')
        ->and($url)->toBe('http://127.0.0.1:1337/dashboard?tab=settings');
});

it('generates HTTP URL using helper', function (): void {
    RouteFacade::get('/dashboard', fn (): Response => response()->plain('Ok'))
        ->name('dashboard');

    $url = url('/dashboard', ['tab' => 'settings']);

    expect($url)->toStartWith('http://')
        ->and($url)->toBe('http://127.0.0.1:1337/dashboard?tab=settings');
});

it('generates a secure HTTPS URL', function (): void {
    $route = new Route();
    $generator = new UrlGenerator($route);

    $url = $generator->secure('/dashboard', ['tab' => 'settings']);

    expect($url)->toStartWith('https://')
        ->and($url)->toBe('https://127.0.0.1:1337/dashboard?tab=settings');
});

it('generates a secure URL without query parameters', function (): void {
    $route = new Route();
    $generator = new UrlGenerator($route);

    $url = $generator->secure('/dashboard');

    expect($url)->toBe('https://127.0.0.1:1337/dashboard');
});

it('generates a signed URL with signature query parameter', function (): void {
    $route = new Route();
    $route->get('/unsubscribe/{user}', fn (): Response => response()->plain('Ok'))
        ->name('unsubscribe');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('unsubscribe', ['user' => 1]);

    expect($url)->toContain('signature=')
        ->and($url)->toStartWith('http://127.0.0.1:1337/unsubscribe/1?signature=');
});

it('generates a signed URL with expiration', function (): void {
    $route = new Route();
    $route->get('/unsubscribe/{user}', fn (): Response => response()->plain('Ok'))
        ->name('unsubscribe');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('unsubscribe', ['user' => 1], expiration: 60);

    expect($url)->toContain('expires=')
        ->and($url)->toContain('signature=');
});

it('generates a temporary signed URL with both expires and signature', function (): void {
    $route = new Route();
    $route->get('/download/{file}', fn (): Response => response()->plain('Ok'))
        ->name('download');

    $generator = new UrlGenerator($route);

    $url = $generator->temporarySignedRoute('download', 300, ['file' => 'report']);

    expect($url)->toContain('expires=')
        ->and($url)->toContain('signature=');
});

it('generates a temporary signed URL with DateInterval expiration', function (): void {
    $route = new Route();
    $route->get('/download/{file}', fn (): Response => response()->plain('Ok'))
        ->name('download');

    $generator = new UrlGenerator($route);

    $url = $generator->temporarySignedRoute('download', new DateInterval('PT1H'), ['file' => 'doc']);

    expect($url)->toContain('expires=')
        ->and($url)->toContain('signature=');

    // Verify the expiration timestamp is roughly 1 hour from now
    preg_match('/expires=(\d+)/', $url, $matches);
    $expires = (int) $matches[1];

    expect($expires)->toBeGreaterThan(time() + 3500)
        ->and($expires)->toBeLessThanOrEqual(time() + 3600);
});

it('generates a temporary signed URL with DateTimeInterface expiration', function (): void {
    $route = new Route();
    $route->get('/download/{file}', fn (): Response => response()->plain('Ok'))
        ->name('download');

    $generator = new UrlGenerator($route);

    $futureTime = new DateTimeImmutable('+30 minutes');
    $url = $generator->temporarySignedRoute('download', $futureTime, ['file' => 'doc']);

    preg_match('/expires=(\d+)/', $url, $matches);
    $expires = (int) $matches[1];

    expect($expires)->toBe($futureTime->getTimestamp());
});

it('validates a correctly signed URL', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('verify', ['token' => 'abc123']);

    $request = createRequest($url);

    expect($generator->hasValidSignature($request))->toBeTrue();
});

it('rejects a tampered signed URL', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('verify', ['token' => 'abc123']);

    // Tamper with the signature
    $tamperedUrl = preg_replace('/signature=[a-f0-9]+/', 'signature=tampered', $url);

    $request = createRequest($tamperedUrl);

    expect($generator->hasValidSignature($request))->toBeFalse();
});

it('rejects a request with missing signature', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    $url = 'http://127.0.0.1:1337/verify/abc123';

    $request = createRequest($url);

    expect($generator->hasValidSignature($request))->toBeFalse();
});

it('rejects an expired signed URL', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    // Create a signed URL that expired 10 seconds ago
    $url = $generator->signedRoute('verify', ['token' => 'abc123'], expiration: -10);

    $request = createRequest($url);

    expect($generator->hasValidSignature($request))->toBeFalse();
});

it('accepts a signed URL without expiration', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('verify', ['token' => 'abc123']);

    $request = createRequest($url);

    expect($generator->signatureHasNotExpired($request))->toBeTrue();
});

it('validates signature ignoring specified query parameters', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('verify', ['token' => 'abc123']);

    // Add an extra query parameter that should be ignored
    $urlWithExtra = $url . '&tracking=utm123';

    $request = createRequest($urlWithExtra);

    expect($generator->hasValidSignature($request, ignoreQuery: ['tracking']))->toBeTrue();
});

it('validates signature with closure-based ignore query', function (): void {
    $route = new Route();
    $route->get('/verify/{token}', fn (): Response => response()->plain('Ok'))
        ->name('verify');

    $generator = new UrlGenerator($route);

    $url = $generator->signedRoute('verify', ['token' => 'abc123']);

    $urlWithExtra = $url . '&fbclid=abc&utm_source=email';

    $request = createRequest($urlWithExtra);

    $ignore = fn (): array => ['fbclid', 'utm_source'];

    expect($generator->hasValidSignature($request, ignoreQuery: $ignore))->toBeTrue();
});

it('resolves route names within groups', function (): void {
    $route = new Route();

    $route->name('admin')
        ->prefix('admin')
        ->group(function (Route $inner) {
            $inner->get('users/{user}', fn (): Response => response()->plain('Ok'))
                ->name('users.show');
        });

    $generator = new UrlGenerator($route);

    $url = $generator->route('admin.users.show', ['user' => 7]);

    expect($url)->toBe('http://127.0.0.1:1337/admin/users/7');
});

it('supports BackedEnum as route name', function (): void {
    $route = new Route();
    $route->get('/settings', fn (): Response => response()->plain('Ok'))
        ->name('settings');

    $generator = new UrlGenerator($route);

    $enum = Tests\Unit\Routing\TestRouteName::SETTINGS;

    $url = $generator->route($enum);

    expect($url)->toBe('http://127.0.0.1:1337/settings');
});

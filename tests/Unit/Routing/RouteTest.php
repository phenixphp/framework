<?php

declare(strict_types=1);

use Phenix\Constants\HttpMethod;
use Phenix\Routing\Route;
use Tests\Unit\Routing\AcceptJsonResponses;
use Tests\Unit\Routing\WelcomeController;
use Tests\Util\AssertRoute;

it('adds get routes successfully', function (string $method, HttpMethod $httpMethod) {
    $router = new Route();

    $router->{$method}('/', fn () => 'Hello')
        ->name('awesome')
        ->middleware([
            AcceptJsonResponses::class,
        ]);

    AssertRoute::from($router)
        ->methodIs($httpMethod)
        ->nameIs('awesome')
        ->hasMiddlewares([AcceptJsonResponses::class]);
})->with([
    ['get', HttpMethod::GET],
    ['post', HttpMethod::POST],
    ['put', HttpMethod::PUT],
    ['patch', HttpMethod::PATCH],
    ['delete', HttpMethod::DELETE],
]);

it('adds get routes with params successfully', function () {
    $router = new Route();

    $router->get('/users/{user}', fn () => 'Hello')
        ->name('users.show');

    AssertRoute::from($router)
        ->methodIs(HttpMethod::GET)
        ->nameIs('users.show')
        ->containsParameters(['user']);
});

it('adds get routes with many params successfully', function () {
    $router = new Route();

    $router->get('/users/{user}/posts/{post}', fn () => 'Hello')
        ->name('users.posts.show');

    AssertRoute::from($router)
        ->methodIs(HttpMethod::GET)
        ->nameIs('users.posts.show')
        ->containsParameters(['user', 'post']);
});

it('can call a class callable method', function () {
    $this->app->register(WelcomeController::class);

    $router = new Route();

    $router->get('/users/{user}/posts/{post}', [WelcomeController::class, 'index'])
        ->name('users.posts.show');

    AssertRoute::from($router)
        ->methodIs(HttpMethod::GET)
        ->nameIs('users.posts.show')
        ->containsParameters(['user', 'post']);
});

it('can add nested route groups', function () {
    $router = new Route();

    $router->middleware(AcceptJsonResponses::class)
        ->name('admin')
        ->prefix('admin')
        ->group(function (Route $route) {
            $route->get('users', fn () => 'User index')
                ->name('users.index');

            $route->get('users/{user}', fn () => 'User details')
                ->name('users.show');

            $route->name('accounting')
                ->prefix('accounting')
                ->group(function (Route $route) {
                    $route->get('invoices', fn () => 'Invoice index')
                        ->name('invoices.index');

                    $route->prefix('payments')
                        ->name('payments')
                        ->group(function (Route $route) {
                            $route->get('pending', fn () => 'Invoice index')
                                ->name('pending.index');
                        });
                });
        });

    $router->get('products', fn () => 'Product index')
        ->name('products.index')
        ->middleware(AcceptJsonResponses::class);

    $expected = [
        [
            'method' => HttpMethod::GET,
            'path' => '/admin/users',
            'middlewares' => [AcceptJsonResponses::class],
            'name' => 'admin.users.index',
        ],
        [
            'method' => HttpMethod::GET,
            'path' => '/admin/users/{user}',
            'middlewares' => [AcceptJsonResponses::class],
            'name' => 'admin.users.show',
        ],
        [
            'method' => HttpMethod::GET,
            'path' => '/admin/accounting/invoices',
            'middlewares' => [AcceptJsonResponses::class],
            'name' => 'admin.accounting.invoices.index',
        ],
        [
            'method' => HttpMethod::GET,
            'path' => '/admin/accounting/payments/pending',
            'middlewares' => [AcceptJsonResponses::class],
            'name' => 'admin.accounting.payments.pending.index',
        ],
        [
            'method' => HttpMethod::GET,
            'path' => '/products',
            'middlewares' => [AcceptJsonResponses::class],
            'name' => 'products.index',
        ],
    ];

    $routes = $router->toArray();

    foreach ($expected as $index => $route) {
        AssertRoute::from($routes[$index])
            ->methodIs($route['method'])
            ->pathIs($route['path'])
            ->hasMiddlewares($route['middlewares'])
            ->nameIs($route['name']);
    }
});

it('can create route group from group method', function () {
    $router = new Route();

    $router->group(function (Route $route) {
        $route->get('users', fn () => 'User index')
            ->name('users.index');
    })
    ->middleware(AcceptJsonResponses::class)
    ->name('admin')
    ->prefix('admin');

    AssertRoute::from($router)
        ->methodIs(HttpMethod::GET)
        ->nameIs('admin.users.index')
        ->pathIs('/admin/users');
});

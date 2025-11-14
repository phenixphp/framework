<?php

declare(strict_types=1);

use Phenix\Auth\User;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Route;
use Phenix\Http\Middlewares\Authenticated;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Util\Date;
use Phenix\Util\Str;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

afterEach(function (): void {
    $this->app->stop();
});

it('requires authentication', function (): void {
    Route::get('/', fn (): Response => response()->plain('Hello'))
        ->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/')
        ->assertUnauthorized();
});

it('authenticates user with valid token', function (): void {
    $user = new User();
    $user->id = 1;
    $user->name = 'John Doe';
    $user->email = 'john@example.com';
    $user->createdAt = Date::now();

    $userData = [
        [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->createdAt->toDateTimeString(),
        ],
    ];

    $tokenData = [
        [
            'id' => Str::uuid()->toString(),
            'tokenable_type' => $user::class,
            'tokenable_id' => $user->id,
            'name' => 'api-token',
            'token' => hash('sha256', 'valid-token'),
            'created_at' => Date::now()->toDateTimeString(),
            'last_used_at' => null,
            'expires_at' => null,
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $tokenResult = new Result([['Query OK']]);
    $tokenResult->setLastInsertedId($tokenData[0]['id']);

    $connection->expects($this->exactly(4))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement($tokenResult), // Create token
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])), // Save last used at update for token
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = $user->createToken('api-token');

    Route::get('/profile', function (Request $request): Response {
        return response()->plain($request->user() instanceof User ? 'Authenticated' : 'Guest');
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/profile', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])
        ->assertOk()
        ->assertBodyContains('Authenticated');
});

it('denies access with invalid token', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result()),
        );

    $this->app->swap(Connection::default(), $connection);

    Route::get('/profile', fn (): Response => response()->json(['message' => 'Authenticated']))
        ->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/profile', headers: [
        'Authorization' => 'Bearer invalid-token',
    ])
        ->assertUnauthorized()
        ->assertJsonFragment(['message' => 'Unauthorized']);
});

it('denies when user is not found', function (): void {
    $user = new User();
    $user->id = 1;
    $user->name = 'John Doe';
    $user->email = 'john@example.com';
    $user->createdAt = Date::now();

    $tokenData = [
        [
            'id' => Str::uuid()->toString(),
            'tokenable_type' => $user::class,
            'tokenable_id' => $user->id,
            'name' => 'api-token',
            'token' => hash('sha256', 'valid-token'),
            'created_at' => Date::now()->toDateTimeString(),
            'last_used_at' => null,
            'expires_at' => null,
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $tokenResult = new Result([['Query OK']]);
    $tokenResult->setLastInsertedId($tokenData[0]['id']);

    $connection->expects($this->exactly(4))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement($tokenResult), // Create token
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])), // Save last used at update for token
            new Statement(new Result()),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = $user->createToken('api-token');

    Route::get('/profile', fn (Request $request): Response => response()->plain('Never reached'))
        ->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/profile', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertUnauthorized();
});

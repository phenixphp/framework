<?php

declare(strict_types=1);

use Phenix\Auth\AuthenticationToken;
use Phenix\Auth\Concerns\HasApiTokens;
use Phenix\Auth\Events\FailedTokenValidation;
use Phenix\Auth\Events\TokenCreated;
use Phenix\Auth\Events\TokenValidated;
use Phenix\Auth\PersonalAccessToken;
use Phenix\Auth\User;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Event;
use Phenix\Facades\Route;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Middlewares\Authenticated;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Util\Date;
use Phenix\Util\Str;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

uses(HasApiTokens::class);

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
    Event::fake();

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

    Event::expect(TokenCreated::class)->toBeDispatched();
    Event::expect(TokenValidated::class)->toBeDispatched();
});

it('denies access with invalid token', function (): void {
    Event::fake();

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

    Event::expect(TokenValidated::class)->toNotBeDispatched();
    Event::expect(FailedTokenValidation::class)->toBeDispatched();
});

it('rate limits failed token validations and sets retry-after header', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->any())
        ->method('prepare')
        ->willReturn(
            new Statement(new Result()),
        );

    $this->app->swap(Connection::default(), $connection);

    Route::get('/limited', fn (): Response => response()->plain('Never reached'))
        ->middleware(Authenticated::class);

    $this->app->run();

    for ($i = 0; $i < 5; $i++) {
        $this->get('/limited', headers: [
            'Authorization' => 'Bearer invalid-token',
            'X-Forwarded-For' => '203.0.113.10',
        ])->assertUnauthorized();
    }

    $this->get('/limited', headers: [
        'Authorization' => 'Bearer invalid-token',
        'X-Forwarded-For' => '203.0.113.10',
    ])->assertStatusCode(HttpStatus::TOO_MANY_REQUESTS)->assertHeaders(['Retry-After' => '300']);
});

it('resets rate limit counter on successful authentication', function (): void {
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

    $plainToken = $this->generateTokenValue();

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(8))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result()), // first 4 failed attempts
            new Statement(new Result()),
            new Statement(new Result()),
            new Statement(new Result()),
            new Statement(new Result($tokenData)), // successful auth attempt
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
            new Statement(new Result()), // final invalid attempt
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/reset', fn (Request $request): Response => response()->plain('ok'))
        ->middleware(Authenticated::class);

    $this->app->run();

    for ($i = 0; $i < 4; $i++) {
        $this->get('/reset', headers: [
            'Authorization' => 'Bearer invalid-token',
            'X-Forwarded-For' => '203.0.113.10',
        ])->assertUnauthorized();
    }

    $this->get('/reset', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
        'X-Forwarded-For' => '203.0.113.10',
    ])->assertOk()->assertHeaderIsMissing('Retry-After');

    $this->get('/reset', headers: [
        'Authorization' => 'Bearer invalid-token',
        'X-Forwarded-For' => '203.0.113.10',
    ])->assertUnauthorized();
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

it('check user can query tokens', function (): void {
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

    $connection->expects($this->exactly(5))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement($tokenResult), // Create token
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])), // Save last used at update for token
            new Statement(new Result($userData)),
            new Statement(new Result($tokenData)), // Query tokens
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = $user->createToken('api-token');

    Route::get('/tokens', function (Request $request): Response {
        return response()->json($request->user()->tokens()->get());
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/tokens', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])
        ->assertOk()
        ->assertJsonFragment([
            'name' => 'api-token',
            'tokenableType' => $user::class,
            'tokenableId' => $user->id,
        ]);
});

it('check user permissions', function (): void {
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

    $plainToken = $this->generateTokenValue();

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->abilities = json_encode(['users.index']);
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/users', function (Request $request): Response {
        if (! $request->can('users.index')) {
            return response()->json([
                'error' => 'Forbidden',
            ], HttpStatus::FORBIDDEN);
        }

        return response()->plain('ok');
    })->middleware(Authenticated::class);

    $this->app->run();

    $response = $this->get('/users', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
        'X-Forwarded-For' => '203.0.113.10',
    ]);

    $response->assertOk()
        ->assertBodyContains('ok');
});

it('denies when abilities is null', function (): void {
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

    $plainToken = 'plain-null-abilities';

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    // abilities stays null on purpose
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            // no abilities field intentionally
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/null-abilities', function (Request $request): Response {
        $canSingle = $request->can('anything.here');
        $canAny = $request->canAny(['one.ability', 'second.ability']);
        $canAll = $request->canAll(['first.required', 'second.required']);

        return response()->plain(($canSingle || $canAny || $canAll) ? 'granted' : 'denied');
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/null-abilities', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertOk()->assertBodyContains('denied');
});

it('grants any ability via wildcard asterisk *', function (): void {
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

    $plainToken = 'plain-wildcard';

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->abilities = json_encode(['*']);
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/wildcard', function (Request $request): Response {
        return response()->plain(
            $request->can('any.ability') &&
            $request->canAny(['first.ability', 'second.ability']) &&
            $request->canAll(['one.ability', 'two.ability']) ? 'ok' : 'fail'
        );
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/wildcard', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertOk()->assertBodyContains('ok');
});

it('canAny passes when at least one matches', function (): void {
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

    $plainToken = 'plain-can-any';

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->abilities = json_encode(['users.index']);
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/can-any', function (Request $request): Response {
        return response()->plain($request->canAny(['users.delete', 'users.index']) ? 'ok' : 'fail');
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/can-any', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertOk()->assertBodyContains('ok');
});

it('canAny fails when none match', function (): void {
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

    $plainToken = 'plain-can-any-fail';

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->abilities = json_encode(['users.index']);
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/can-any-fail', function (Request $request): Response {
        return response()->plain($request->canAny(['users.delete', 'tokens.create']) ? 'ok' : 'fail');
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/can-any-fail', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertOk()->assertBodyContains('fail');
});

it('canAll passes when all match', function (): void {
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

    $plainToken = 'plain-can-all';

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->abilities = json_encode(['users.index', 'users.delete']);
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/can-all', function (Request $request): Response {
        return response()->plain($request->canAll(['users.index', 'users.delete']) ? 'ok' : 'fail');
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/can-all', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertOk()->assertBodyContains('ok');
});

it('canAll fails when one is missing', function (): void {
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

    $plainToken = 'plain-can-all-fail';

    $token = new PersonalAccessToken();
    $token->id = Str::uuid()->toString();
    $token->tokenableType = $user::class;
    $token->tokenableId = $user->id;
    $token->name = 'api-token';
    $token->abilities = json_encode(['users.index']);
    $token->token = hash('sha256', $plainToken);
    $token->createdAt = Date::now();
    $token->expiresAt = Date::now()->addMinutes(10);
    $token->lastUsedAt = null;

    $tokenData = [
        [
            'id' => $token->id,
            'tokenable_type' => $token->tokenableType,
            'tokenable_id' => $token->tokenableId,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'token' => $token->token,
            'created_at' => $token->createdAt->toDateTimeString(),
            'last_used_at' => $token->lastUsedAt,
            'expires_at' => $token->expiresAt->toDateTimeString(),
        ],
    ];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $connection->expects($this->exactly(3))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($tokenData)),
            new Statement(new Result([['Query OK']])),
            new Statement(new Result($userData)),
        );

    $this->app->swap(Connection::default(), $connection);

    $authToken = new AuthenticationToken(
        id: $token->id,
        token: $plainToken,
        expiresAt: $token->expiresAt
    );

    Route::get('/can-all-fail', function (Request $request): Response {
        return response()->plain($request->canAll(['users.index', 'users.delete']) ? 'ok' : 'fail');
    })->middleware(Authenticated::class);

    $this->app->run();

    $this->get('/can-all-fail', headers: [
        'Authorization' => 'Bearer ' . $authToken->toString(),
    ])->assertOk()->assertBodyContains('fail');
});

it('returns false when user present but no token', function (): void {
    $user = new User();
    $user->id = 1;
    $user->name = 'John Doe';
    $user->email = 'john@example.com';
    $user->createdAt = Date::now();

    // No DB, no middleware: manually attach user without token
    Route::get('/no-token', function (Request $request) use ($user): Response {
        $request->setUser($user); // user has no currentAccessToken

        return response()->plain($request->can('users.index') ? 'ok' : 'fail');
    });

    $this->app->run();

    $this->get('/no-token')->assertOk()->assertBodyContains('fail');
});

it('returns false when no user', function (): void {
    Route::get('/no-user', function (Request $request): Response {
        return response()->plain($request->can('users.index') ? 'ok' : 'fail');
    });

    $this->app->run();

    $this->get('/no-user')->assertOk()->assertBodyContains('fail');
});

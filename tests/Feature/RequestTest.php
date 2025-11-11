<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\RequestBody;
use Phenix\Facades\Route;
use Phenix\Http\Constants\ContentType;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Http\Request;
use Phenix\Http\Response;
use Phenix\Testing\TestResponse;
use Tests\Unit\Routing\AcceptJsonResponses;

afterEach(function (): void {
    $this->app->stop();
});

it('can send requests to server', function (): void {
    Route::get('/', fn () => response()->plain('Hello'))
        ->middleware(AcceptJsonResponses::class);

    $this->app->run();

    $this->get('/')
        ->assertOk()
        ->assertBodyContains('Hello');

    $this->get(path: '/', headers: ['Accept' => 'text/html'])
        ->assertNotAcceptable();

    $this->get('/users')
        ->assertNotFound();

    $this->post('/users', ['name' => 'John Doe'])
        ->assertNotFound();

    $this->put('/users/1')
        ->assertNotFound();

    $this->patch('/users/1')
        ->assertNotFound();

    $this->delete('/users/1')
        ->assertNotFound();
});

it('can decode x-www-form-urlencode body', function (): void {
    Route::post('/posts', function (Request $request) {
        expect($request->body()->has('title'))->toBeTruthy();
        expect($request->body('title'))->toBe('Post title');
        expect($request->body()->integer('age'))->toBe(18);
        expect($request->body()->integer('weight'))->toBeNull();
        expect($request->body()->hasFile('file'))->toBeFalsy();
        expect($request->getHeader('content-type'))->toStartWith(ContentType::FORM_URLENCODED->value);
        expect($request->getClient())->toBeInstanceOf(Client::class);
        expect($request->getProtocolVersion())->toBe('1.1');
        expect($request->getBody())->toBeInstanceOf(RequestBody::class);
        expect($request->getCookies())->toBe([]);
        expect($request->getCookie('test'))->toBeNull();
        expect($request->removeCookie('test'))->toBeNull();

        return response()->json($request);
    });

    $this->app->run();

    $body = new Form();
    $body->addField('title', 'Post title');
    $body->addField('content', 'Post content');
    $body->addField('age', '18');

    $this->post('/posts', $body)
        ->assertOk();
});

it('can decode multipart form data body', function (): void {
    Route::post('/files', function (Request $request) {
        expect($request->body()->has('description'))->toBeTruthy();
        expect($request->body()->has('file'))->toBeTruthy();
        expect($request->body()->hasFile('file'))->toBeTruthy();
        expect($request->body()->get('file'))->toBeInstanceOf(BufferedFile::class);
        expect($request->body()->getFile('file'))->toBeInstanceOf(BufferedFile::class);
        expect($request->body()->files())->toHaveCount(1);
        expect($request->toArray())->toHaveCount(3);

        expect($request->getHeader('content-type'))->toStartWith(ContentType::FORM_DATA->value);

        return response()->json(['message' => 'Ok']);
    });

    $this->app->run();

    $body = new Form();
    $body->addField('description', 'Upload file');
    $body->addField('options', 'one');
    $body->addField('options', 'two');

    $file = __DIR__ . '/../fixtures/files/lorem.txt';
    $body->addFile('file', $file);

    $this->post('/files', $body)
        ->assertOk();
});

it('responds with a view', function (): void {
    Route::get('/users', function (): Response {
        return response()->view('users.index', [
            'title' => 'New title',
        ]);
    });

    $this->app->run();

    /** @var TestResponse $response */
    $response = $this->get('/users');

    $response->assertOk()
        ->assertHeaderContains(['Content-Type' => 'text/html; charset=utf-8'])
        ->assertBodyContains('<body>')
        ->assertBodyContains('User index');
});

it('can assert response is html', function (): void {
    Route::get('/page', function (): Response {
        return response()->view('users.index', [
            'title' => 'Test Page',
        ]);
    });

    $this->app->run();

    $this->get('/page')
        ->assertOk()
        ->assertIsHtml()
        ->assertBodyContains('<body>');
});

it('can assert response is plain text', function (): void {
    Route::get('/text', function (): Response {
        return response()->plain('This is plain text content');
    });

    $this->app->run();

    $this->get('/text')
        ->assertOk()
        ->assertIsPlainText()
        ->assertBodyContains('plain text');
});

it('can assert json contains', function (): void {
    Route::get('/api/user', function (): Response {
        return response()->json([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin',
        ]);
    });

    $this->app->run();

    $this->get('/api/user')
        ->assertOk()
        ->assertIsJson()
        ->assertJsonPath('data.id', 1)
        ->assertJsonPath('data.name', 'John Doe');
});

it('can assert json does not contain', function (): void {
    Route::get('/api/user', function (): Response {
        return response()->json([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    $this->app->run();

    $this->get('/api/user')
        ->assertOk()
        ->assertJsonDoesNotContain([
            'name' => 'Jane Doe',
            'password' => 'secret',
        ])
        ->assertJsonPathNotEquals('data.name', 'Jane Doe');
});

it('can assert json fragment', function (): void {
    Route::get('/api/posts', function (): Response {
        return response()->json([
            [
                'id' => 1,
                'title' => 'First Post',
                'author' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ],
            [
                'id' => 2,
                'title' => 'Second Post',
                'author' => [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                ],
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/posts')
        ->assertOk()
        ->assertJsonFragment([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
        ->assertJsonFragment([
            'id' => 2,
            'title' => 'Second Post',
        ]);
});

it('can assert json missing fragment', function (): void {
    Route::get('/api/posts', function (): Response {
        return response()->json([
            [
                'id' => 1,
                'title' => 'First Post',
                'author' => [
                    'name' => 'John Doe',
                ],
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/posts')
        ->assertOk()
        ->assertJsonMissingFragment([
            'name' => 'Jane Smith',
        ])
        ->assertJsonMissingFragment([
            'title' => 'Third Post',
        ]);
});

it('can assert json path', function (): void {
    Route::get('/api/profile', function (): Response {
        return response()->json([
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'age' => 30,
                ],
                'settings' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
            'posts' => [
                ['id' => 1, 'title' => 'First'],
                ['id' => 2, 'title' => 'Second'],
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/profile')
        ->assertOk()
        ->assertJsonPath('data.user.profile.name', 'John Doe')
        ->assertJsonPath('data.user.profile.age', 30)
        ->assertJsonPath('data.user.settings.theme', 'dark')
        ->assertJsonPath('data.user.settings.notifications', true)
        ->assertJsonPath('data.posts.0.title', 'First')
        ->assertJsonPath('data.posts.1.id', 2);
});

it('can assert json path not equals', function (): void {
    Route::get('/api/user', function (): Response {
        return response()->json([
            'user' => [
                'name' => 'John Doe',
                'role' => 'admin',
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/user')
        ->assertOk()
        ->assertJsonPathNotEquals('data.user.name', 'Jane Doe')
        ->assertJsonPathNotEquals('data.user.role', 'user');
});

it('can assert json structure', function (): void {
    Route::get('/api/users', function (): Response {
        return response()->json([
            'users' => [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                [
                    'id' => 2,
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                ],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/users')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'users' => [
                    '*' => ['id', 'name', 'email'],
                ],
                'meta' => ['total', 'page'],
            ],
        ]);
});

it('can assert json structure with nested arrays', function (): void {
    Route::get('/api/posts', function (): Response {
        return response()->json([
            [
                'id' => 1,
                'title' => 'First Post',
                'author' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
                'comments' => [
                    ['id' => 1, 'body' => 'Great!'],
                    ['id' => 2, 'body' => 'Nice!'],
                ],
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/posts')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author' => ['name', 'email'],
                    'comments' => [
                        '*' => ['id', 'body'],
                    ],
                ],
            ],
        ]);
});

it('can assert json count', function (): void {
    Route::get('/api/items', function (): Response {
        return response()->json([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ]);
    });

    $this->app->run();

    $this->get('/api/items')
        ->assertOk()
        ->assertJsonPath('data.0.id', 1)
        ->assertJsonPath('data.1.id', 2)
        ->assertJsonPath('data.2.id', 3)
        ->assertJsonCount(3, 'data');
});

it('can chain multiple json assertions', function (): void {
    Route::get('/api/data', function (): Response {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'user' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);
    });

    $this->app->run();

    $this->get('/api/data')
        ->assertOk()
        ->assertIsJson()
        ->assertJsonFragment(['name' => 'John Doe'])
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.code', 200)
        ->assertJsonPath('data.user.id', 1)
        ->assertJsonPath('data.user.email', 'john@example.com')
        ->assertJsonStructure([
            'data' => [
                'status',
                'code',
                'user' => ['id', 'name', 'email'],
            ],
        ])
        ->assertJsonPathNotEquals('data.status', 'error')
        ->assertJsonMissingFragment(['error' => 'Something went wrong']);
});

it('can assert record was created', function (): void {
    Route::post('/api/users', function (): Response {
        return response()->json([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], HttpStatus::CREATED);
    });

    $this->app->run();

    $this->post('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ])
        ->assertCreated()
        ->assertStatusCode(HttpStatus::CREATED)
        ->assertJsonFragment(['name' => 'John Doe'])
        ->assertJsonPath('data.id', 1)
        ->assertJsonPath('data.email', 'john@example.com')
        ->assertJsonContains([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], 'data')
        ->assertJsonDoesNotContain([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], 'data');
});

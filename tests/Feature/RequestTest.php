<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\RequestBody;
use Phenix\Constants\ContentType;
use Phenix\Facades\Route;
use Phenix\Http\Request;
use Tests\Unit\Routing\AcceptJsonResponses;

afterEach(function () {
    $this->app->stop();
});

it('can send requests to server', function () {
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

it('can decode x-www-form-urlencode body', function () {
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

it('can decode multipart form data body', function () {
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

<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Phenix\Constants\ContentType;
use Phenix\Facades\Route;
use Phenix\Http\Requests\FormRequest;
use Tests\Unit\Routing\AcceptJsonResponses;

afterEach(function () {
    $this->app->stop();
});

it('can send requests to server', function () {
    Route::get('/', fn () => new Response(body: 'Hello'))
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
        $formRequest = FormRequest::fromRequest($request);

        expect($formRequest->body()->has('title'))->toBeTruthy();
        expect($formRequest->body('title'))->toBe('Post title');
        expect($formRequest->body()->integer('age'))->toBe(18);
        expect($formRequest->body()->integer('weight'))->toBeNull();
        expect($formRequest->body()->hasFile('file'))->toBeFalsy();

        expect($request->getHeader('content-type'))->toStartWith(ContentType::FORM_URLENCODED->value);

        return new Response(body: json_encode($formRequest->toArray()));
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
        $formRequest = FormRequest::fromRequest($request);

        expect($formRequest->body()->has('description'))->toBeTruthy();
        expect($formRequest->body()->hasFile('file'))->toBeTruthy();
        expect($formRequest->body()->getFile('file'))->toBeInstanceOf(BufferedFile::class);
        expect($formRequest->body()->files())->toHaveCount(1);
        expect($formRequest->toArray())->toHaveCount(3);

        expect($request->getHeader('content-type'))->toStartWith(ContentType::FORM_DATA->value);

        return new Response(body: json_encode(['message' => 'Ok']));
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

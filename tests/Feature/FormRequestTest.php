<?php

declare(strict_types=1);

use Amp\Http\Client\Form;
use Phenix\Facades\Route;
use Phenix\Http\Requests\StreamParser;
use Phenix\Http\Response;
use Phenix\Testing\TestResponse;
use Tests\Feature\Requests\StoreUserRequest;
use Tests\Feature\Requests\StreamedRequest;

afterEach(function () {
    $this->app->stop();
});

it('validates requests using custom form request', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john.doe@email.com',
    ];

    Route::post('/users', function (StoreUserRequest $request) use ($data): Response {
        expect($request->body('name'), $data['name']);
        expect($request->body('email'), $data['email']);

        expect($request->isValid())->toBeTrue();
        expect($request->validated())->toBe($data);

        return response()->plain('Ok');
    });

    $this->app->run();

    $this->post('/users', $data)
        ->assertOk();
});

it('responds with unprocessable entity due invalid data', function () {
    $data = [
        'name' => 'John Doe and the deathly hallows',
        'email' => 'john.doe',
    ];

    Route::post('/users', function (StoreUserRequest $request): Response {
        return response()->plain('Ok');
    });

    $this->app->run();

    /** @var TestResponse $response */
    $response = $this->post('/users', $data);

    $response->assertUnprocessableEntity();

    $body = json_decode($response->getBody(), true);

    expect($body['data'])->toHaveKeys(['name', 'email']);
});

it('validates requests using streamed form request', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john.doe@email.com',
    ];

    Route::post('/users', function (StreamedRequest $request) use ($data): Response {
        expect($request->body())->toBeInstanceOf(StreamParser::class);

        expect($request->body('name'), $data['name']);
        expect($request->body('email'), $data['email']);
        expect($request->body()->hasFile('avatar'))->toBeTrue();

        expect($request->isValid())->toBeTrue();
        expect($request->validated())->toBe($data);

        return response()->plain('Ok');
    });

    $this->app->run();

    $body = new Form();
    $body->addField('name', 'John Doe', );
    $body->addField('email', 'john.doe@email.com');

    $file = __DIR__ . '/../fixtures/files/user.png';
    $body->addFile('avatar', $file);

    $this->post('/users', $body)
        ->assertOk();
});

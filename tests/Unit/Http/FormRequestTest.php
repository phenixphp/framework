<?php

declare(strict_types=1);

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use League\Uri\Http;
use Phenix\Constants\HttpMethod;
use Phenix\Http\Requests\FormRequest;
use Phenix\Util\URL;

it('gets route attributes from server request', function () {
    $client = $this->createMock(Client::class);
    $uri = Http::new(URL::build('posts/7/comments/22'));
    $request = new Request($client, HttpMethod::GET->value, $uri);

    $args = ['post' => '7', 'comment' => '22'];
    $request->setAttribute(Router::class, $args);

    $attributes = FormRequest::fromRequest($request);

    expect($attributes->route('post'))->toBe('7');
    expect($attributes->route('comment'))->toBe('22');
    expect($attributes->route()->integer('post'))->toBe(7);
    expect($attributes->route()->has('post'))->toBeTrue();
    expect($attributes->route()->has('user'))->toBeFalse();
    expect($attributes->route()->toArray())->toBe($args);
});

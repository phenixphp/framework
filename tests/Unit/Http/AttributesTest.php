<?php

declare(strict_types=1);

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use League\Uri\Http;
use Phenix\Constants\HttpMethods;
use Phenix\Http\Attributes;
use Phenix\Util\URL;

it('gets route attributes from server request', function () {
    $client = $this->createMock(Client::class);
    $uri = Http::new(URL::build('posts/7/comments/22'));
    $request = new Request($client, HttpMethods::GET->value, $uri);

    $args = ['post' => '7', 'comment' => '22'];
    $request->setAttribute(Router::class, $args);

    $attributes = Attributes::fromRequest($request);

    expect($attributes->get('post'))->toBe('7');
    expect($attributes->get('comment'))->toBe('22');
    expect($attributes->integer('post'))->toBe(7);
    expect($attributes->has('post'))->toBeTrue();
    expect($attributes->has('user'))->toBeFalse();
    expect($attributes->toArray())->toBe($args);

    $attributes->set('user', 1);

    expect($attributes->has('user'))->toBeTrue();

    $attributes->remove('user');

    expect($attributes->has('user'))->toBeFalse();
});

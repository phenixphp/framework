<?php

declare(strict_types=1);

use Amp\Future;
use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Router;
use Amp\Http\Server\Trailers;
use League\Uri\Http;
use Phenix\Constants\HttpMethod;
use Phenix\Http\Request;
use Phenix\Util\URL;
use Psr\Http\Message\UriInterface;

it('gets route attributes from server request', function () {
    $client = $this->createMock(Client::class);
    $uri = Http::new(URL::build('posts/7/comments/22'));
    $request = new ServerRequest($client, HttpMethod::GET->value, $uri);

    $args = ['post' => '7', 'comment' => '22'];
    $request->setAttribute(Router::class, $args);

    $formRequest = new Request($request);

    expect($formRequest->route('post'))->toBe('7');
    expect($formRequest->route('comment'))->toBe('22');
    expect($formRequest->route()->integer('post'))->toBe(7);
    expect($formRequest->route()->has('post'))->toBeTrue();
    expect($formRequest->route()->has('user'))->toBeFalse();
    expect($formRequest->route()->toArray())->toBe($args);
});

it('gets query parameters from server request', function () {
    $client = $this->createMock(Client::class);
    $uri = Http::new(URL::build('posts?page=1&per_page=15&status[]=active&status[]=inactive&object[one]=1&object[two]=2'));
    $request = new ServerRequest($client, HttpMethod::GET->value, $uri);

    $formRequest = new Request($request);

    expect($formRequest->query('page'))->toBe('1');
    expect($formRequest->query('per_page'))->toBe('15');
    expect($formRequest->query()->get('per_page'))->toBe('15');
    expect($formRequest->query('status'))->toBe(['active', 'inactive']);
    expect($formRequest->query('object'))->toBe(['one' => '1', 'two' => '2']);
    expect($formRequest->hasQueryParameter('page'))->toBeTrue();
    expect($formRequest->getQueryParameters())->toBe([
        'page' => '1',
        'per_page' => '15',
        'status' => ['active', 'inactive'],
        'object' => [
            'one' => '1',
            'two' => '2',
        ],
    ]);

    $formRequest->setQueryParameter('set', '1');

    expect($formRequest->getQueryParameter('set'))->toBe('1');

    $formRequest->addQueryParameter('add', '2');

    expect($formRequest->getQueryParameter('add'))->toBe('2');

    $formRequest->setQueryParameters(['collection' => 'value']);

    expect($formRequest->getQueryParameter('collection'))->toBe('value');

    $formRequest->replaceQueryParameters(['collection' => 'new value']);

    expect($formRequest->getQueryParameter('collection'))->toBe('new value');

    $formRequest->removeQueryParameter('collection');

    expect($formRequest->hasQueryParameter('collection'))->toBeFalse();

    $formRequest->setQueryParameter('new_key', 'value');

    expect($formRequest->removeQuery())->toBeNull();

    $formRequest->setHeader('Content-Type', 'application/json');

    expect($formRequest->getHeader('Content-Type'))->toBe('application/json');

    $formRequest->removeHeader('Content-Type');

    expect($formRequest->hasHeader('Content-Type'))->toBeFalse();

    $formRequest->setHeaders(['Content-Type' => 'application/json']);

    expect($formRequest->getHeader('Content-Type'))->toBe('application/json');

    $formRequest->addHeader('Accept', 'application/json');

    expect($formRequest->hasHeader('Accept'))->toBeTrue();

    $formRequest->replaceHeaders(['content-type' => 'text/plain']);

    expect($formRequest->getHeaders())->toBe([
        'content-type' => 'text/plain',
        'accept' => 'application/json',
    ]);

    expect($formRequest->isIdempotent())->toBeTrue();
    expect($formRequest->getUri())->toBeInstanceOf(UriInterface::class);
    expect($formRequest->getMethod())->toBe(HttpMethod::GET->value);

    $future = Future::complete(['fooHeader' => 'barValue']);

    $trailers = new Trailers($future, ['fooHeader']);

    $formRequest->setTrailers($trailers);

    expect($formRequest->getTrailers())->toBeInstanceOf(Trailers::class);

    $formRequest->removeTrailers();

    expect($formRequest->getTrailers())->toBeNull();

    $formRequest->setCookie(new RequestCookie('test', 'cookie_value'));

    expect($formRequest->getCookie('test'))->toBeInstanceOf(RequestCookie::class);

    $formRequest->setBody('{"key":"value"}');

    expect($formRequest->getBody())->toBeInstanceOf(RequestBody::class);
});

it('can decode JSON body', function () {
    $client = $this->createMock(Client::class);

    $body = [
        'title' => 'Article title',
        'content' => 'Article content',
        'rate' => 10,
    ];

    $uri = Http::new(URL::build('posts'));

    $request = new ServerRequest($client, HttpMethod::POST->value, $uri);
    $request->setHeader('content-type', 'application/json');
    $request->setBody(json_encode($body));

    $formRequest = new Request($request);

    expect($formRequest->body()->has('title'))->toBeTruthy();
    expect($formRequest->body()->get('title'))->toBe($body['title']);
    expect($formRequest->body('title'))->toBe($body['title']);
    expect($formRequest->body('content'))->toBe($body['content']);
    expect($formRequest->body()->integer('content'))->toBeNull();
    expect($formRequest->body()->integer('rate'))->toBe(10);
    expect($formRequest->body()->integer('other'))->toBeNull();
    expect($formRequest->body()->hasFile('file'))->toBeFalsy();
    expect($formRequest->body()->getFile('file'))->toBeNull();
    expect($formRequest->body()->files())->toHaveCount(0);
    expect($formRequest->body()->integer('other'))->toBeNull();
    expect($formRequest->body()->toArray())->toBe($body);
});

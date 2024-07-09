<?php

declare(strict_types=1);

use Amp\Http\Server\Response as ServerResponse;
use Phenix\Data\Collection;
use Phenix\Http\Response;

it('responds plain text', function () {
    $response = new Response();

    $serverResponse = $response->plain('Hello world!')->send();

    expect($serverResponse)->toBeInstanceOf(ServerResponse::class);
    expect($serverResponse->getBody()->read())->toBe('Hello world!');
    expect($serverResponse->getHeader('Content-Type'))->toBe('text/plain');
});

it('responds json data from plain array', function () {
    $data = ['name' => 'John Doe'];

    $response = new Response();

    $serverResponse = $response->json($data)->send();

    expect($serverResponse)->toBeInstanceOf(ServerResponse::class);
    expect($serverResponse->getBody()->read())->toContain(json_encode($data));
    expect($serverResponse->getHeader('Content-Type'))->toBe('application/json');
});

it('responds json data from arrayable', function () {
    $data = ['name' => 'John Doe'];

    $collection = new Collection('array');
    $collection->add($data);

    $response = new Response();

    $serverResponse = $response->json($collection)->send();

    expect($serverResponse)->toBeInstanceOf(ServerResponse::class);
    expect($serverResponse->getBody()->read())->toContain(json_encode($data));
});

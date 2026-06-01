<?php

declare(strict_types=1);

use Amp\Http\Server\Response as ServerResponse;
use Phenix\Data\Collection;
use Phenix\Http\Response;
use Phenix\Http\ServerSentEvent;

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

it('responds event streams from raw frames', function () {
    $response = new Response();

    $serverResponse = $response->eventStream([
        "event: notification\ndata: Event 0",
        "event: notification\ndata: Event 1\n\n",
    ])->send();

    expect($serverResponse)->toBeInstanceOf(ServerResponse::class);
    expect($serverResponse->getHeader('Content-Type'))->toBe('text/event-stream; charset=utf-8');
    expect($serverResponse->getHeader('Cache-Control'))->toBe('no-cache');
    expect($serverResponse->getBody()->read())->toBe("event: notification\ndata: Event 0\n\n");
    expect($serverResponse->getBody()->read())->toBe("event: notification\ndata: Event 1\n\n");
});

it('responds event streams from server sent events', function () {
    $response = new Response();

    $serverResponse = $response->eventStream([
        new ServerSentEvent(
            data: "First line\nSecond line",
            event: 'notification',
            id: 'event-1',
            retry: 500,
            comment: 'initial event'
        ),
    ])->send();

    expect($serverResponse)->toBeInstanceOf(ServerResponse::class);
    expect($serverResponse->getBody()->read())->toBe(
        ": initial event\n"
        . "event: notification\n"
        . "id: event-1\n"
        . "retry: 500\n"
        . "data: First line\n"
        . "data: Second line\n\n"
    );
});

it('responds event streams from closures', function () {
    $response = new Response();

    $serverResponse = $response->eventStream(function (): iterable {
        yield new ServerSentEvent(
            data: 'Event 0',
            event: 'notification',
            id: 'event-0'
        );
    })->send();

    expect($serverResponse)->toBeInstanceOf(ServerResponse::class);
    expect($serverResponse->getBody()->read())->toBe(
        "event: notification\n"
        . "id: event-0\n"
        . "data: Event 0\n\n"
    );
});

it('rejects event stream closures that do not return iterables', function () {
    $response = new Response();

    expect(fn (): Response => $response->eventStream(fn (): string => 'invalid'))
        ->toThrow(InvalidArgumentException::class, 'The event stream closure must return an iterable.');
});

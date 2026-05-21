<?php

declare(strict_types=1);

use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Phenix\Data\Collection;
use Phenix\Http\Client\Exceptions\RequestException;
use Phenix\Http\Client\Response;

it('wraps amp responses and exposes response data helpers', function (): void {
    $ampResponse = new AmpResponse(
        '1.1',
        201,
        null,
        ['Content-Type' => 'application/json'],
        '{"user":{"name":"Ada"},"tags":["php","amp"]}',
        new Request('https://phenix.test')
    );

    $response = new Response($ampResponse);

    expect($response->getClientResponse())->toBe($ampResponse)
        ->and($response->body())->toBe('{"user":{"name":"Ada"},"tags":["php","amp"]}')
        ->and($response->json('user.name'))->toBe('Ada')
        ->and($response->json('missing', 'fallback'))->toBe('fallback')
        ->and($response->object()->user->name)->toBe('Ada')
        ->and($response->collect('tags'))->toBeInstanceOf(Collection::class)
        ->and($response->collect('tags')->toArray())->toBe(['php', 'amp'])
        ->and($response->status())->toBe(201);
});

it('reports common status helpers', function (): void {
    $request = new Request('https://phenix.test');

    expect((new Response(new AmpResponse('1.1', 200, null, [], '', $request)))->ok())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 201, null, [], '', $request)))->created())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 202, null, [], '', $request)))->accepted())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 204, null, [], '', $request)))->noContent())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 301, null, [], '', $request)))->movedPermanently())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 302, null, [], '', $request)))->found())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 400, null, [], '', $request)))->badRequest())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 401, null, [], '', $request)))->unauthorized())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 402, null, [], '', $request)))->paymentRequired())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 403, null, [], '', $request)))->forbidden())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 404, null, [], '', $request)))->notFound())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 408, null, [], '', $request)))->requestTimeout())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 409, null, [], '', $request)))->conflict())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 422, null, [], '', $request)))->unprocessableEntity())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 429, null, [], '', $request)))->tooManyRequests())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 500, null, [], '', $request)))->serverError())->toBeTrue()
        ->and((new Response(new AmpResponse('1.1', 500, null, [], '', $request)))->failed())->toBeTrue();
});

it('exposes response state and headers', function (): void {
    $redirect = new Response(new AmpResponse(
        '1.1',
        302,
        null,
        ['Location' => 'https://phenix.test/next'],
        '',
        new Request('https://phenix.test')
    ));

    $clientError = new Response(new AmpResponse(
        '1.1',
        404,
        null,
        ['Content-Type' => 'application/json'],
        '{"message":"Not found"}',
        new Request('https://phenix.test/missing')
    ));

    expect($redirect->redirect())->toBeTrue()
        ->and($redirect->successful())->toBeFalse()
        ->and($redirect->failed())->toBeFalse()
        ->and($redirect->header('location'))->toBe('https://phenix.test/next')
        ->and($redirect->headers())->toBe(['location' => ['https://phenix.test/next']])
        ->and($clientError->clientError())->toBeTrue()
        ->and($clientError->serverError())->toBeFalse()
        ->and($clientError->failed())->toBeTrue()
        ->and($clientError->header('content-type'))->toBe('application/json')
        ->and($clientError->headers())->toBe(['content-type' => ['application/json']]);
});

it('throws request exceptions for failed responses', function (): void {
    $response = new Response(new AmpResponse(
        '1.1',
        404,
        null,
        [],
        'Missing',
        new Request('https://phenix.test')
    ));

    try {
        $response->throw();
    } catch (RequestException $exception) {
        expect($exception->response())->toBe($response)
            ->and($exception->getCode())->toBe(404)
            ->and($exception->getMessage())->toBe('HTTP request returned status code 404.');

        return;
    }

    expect(false)->toBeTrue();
});

it('runs error callbacks only for failed responses', function (): void {
    $successful = new Response(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        'Ok',
        new Request('https://phenix.test')
    ));

    $failed = new Response(new AmpResponse(
        '1.1',
        500,
        null,
        [],
        'Server error',
        new Request('https://phenix.test')
    ));

    $handled = [];

    $successfulResult = $successful->onError(function (Response $response) use (&$handled): void {
        $handled[] = $response->status();
    });

    $failedResult = $failed->onError(function (Response $response) use (&$handled): void {
        $handled[] = $response->status();
    });

    expect($successfulResult)->toBe($successful)
        ->and($failedResult)->toBe($failed)
        ->and($handled)->toBe([500]);
});

it('does not throw for successful responses', function (): void {
    $response = new Response(new AmpResponse(
        '1.1',
        200,
        null,
        [],
        'Ok',
        new Request('https://phenix.test')
    ));

    expect($response->throw())->toBe($response)
        ->and($response->throwIf(true))->toBe($response);
});

it('throws conditionally with throw if', function (): void {
    $response = new Response(new AmpResponse(
        '1.1',
        500,
        null,
        [],
        'Server error',
        new Request('https://phenix.test')
    ));

    expect($response->throwIf(false))->toBe($response)
        ->and(fn () => $response->throwIf(fn (Response $response): bool => $response->serverError()))
        ->toThrow(RequestException::class);
});

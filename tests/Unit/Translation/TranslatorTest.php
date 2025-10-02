<?php

declare(strict_types=1);

use Phenix\Facades\Translator;
use Phenix\Translation\Translator as Trans;

it('returns key when translation missing', function (): void {
    $translator = new Trans('en', 'en');

    $missing = $translator->get('missing.key');

    expect($missing)->toBe('missing.key');
});

it('can load simple catalogue and retrieve translation', function (): void {
    $translator = new Trans('en', 'en', [
        'en' => [
            'users' => [
                'greeting' => 'Hello',
                'apples' => 'No apples|One apple|:count apples',
                'welcome' => 'Welcome, :name',
            ],
        ],
    ]);

    $greeting = $translator->get('users.greeting');
    $zero = $translator->choice('users.apples', 0);
    $one = $translator->choice('users.apples', 1);
    $many = $translator->choice('users.apples', 5);
    $welcome = $translator->get('users.welcome', ['name' => 'John']);

    expect($greeting)->toBe('Hello');
    expect($zero)->toBe('No apples');
    expect($one)->toBe('One apple');
    expect($many)->toBe('5 apples');
    expect($welcome)->toBe('Welcome, John');
});

it('can translate using facade', function (): void {
    expect(Translator::get('users.greeting'))->toBe('Hello');
});

it('can translate choice using functions', function (): void {
    expect(trans('users.greeting'))->toBe('Hello');
    expect(trans_choice('users.apples', 1))->toBe('users.apples');
});

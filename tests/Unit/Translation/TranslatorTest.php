<?php

declare(strict_types=1);

use Phenix\Facades\File;
use Phenix\Util\Directory;
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
            ],
        ],
    ]);

    $greeting = $translator->get('users.greeting');
    $zero = $translator->choice('users.apples', 0);
    $one = $translator->choice('users.apples', 1);
    $many = $translator->choice('users.apples', 5);

    expect($greeting)->toBe('Hello');
    expect($zero)->toBe('No apples');
    expect($one)->toBe('One apple');
    expect($many)->toBe('5 apples');
});

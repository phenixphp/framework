<?php

declare(strict_types=1);

use Phenix\Facades\Translator;
use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;
use Phenix\Translation\Translator as Trans;

it('returns key when translation missing', function (): void {
    $translator = new Trans('en', 'en');

    $missing = $translator->get('missing.key');

    expect($missing)->toBe('missing.key');
});

it('loads catalogue and retrieves translations with replacements & pluralization', function () {
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

it('facade translation works', function () {
    expect(Translator::get('users.greeting'))->toBe('Hello');
    expect(Translator::getLocale())->toBe('en');
});

it('can translate choice using helper functions', function (): void {
    expect(trans('users.greeting'))->toBe('Hello');
    expect(trans_choice('users.apples', 1))->toBe('users.apples');
});

it('placeholder variant replacements', function () {
    $translator = new Trans('en', 'en', [
        'en' => [
            'messages' => [
                'hello' => 'Hello :name :Name :NAME',
            ],
        ],
    ]);

    expect($translator->get('messages.hello', ['name' => 'john']))->toBe('Hello john John JOHN');
});

it('pluralization three forms', function () {
    $translator = new Trans('en', 'en', [
        'en' => [
            'stats' => [
                'apples' => 'No apples|One apple|:count apples',
            ],
        ],
    ]);

    expect($translator->choice('stats.apples', 0))->toBe('No apples');
    expect($translator->choice('stats.apples', 1))->toBe('One apple');
    expect($translator->choice('stats.apples', 7))->toBe('7 apples');
});

it('pluralization two forms', function () {
    $translator = new Trans('en', 'en', [
        'en' => [
            'stats' => [
                'files' => 'One file|:count files',
            ],
        ],
    ]);

    expect($translator->choice('stats.files', 0))->toBe('0 files');
    expect($translator->choice('stats.files', 1))->toBe('One file');
    expect($translator->choice('stats.files', 2))->toBe('2 files');
});

it('accepts array for count parameter', function () {
    $items = ['a','b','c','d'];

    $translator = new Trans('en', 'en', [
        'en' => [
            'stats' => [
                'items' => 'No items|One item|:count items',
            ],
        ],
    ]);

    expect($translator->choice('stats.items', $items))->toBe('4 items');
});

it('fallback locale used when key missing', function () {
    $translator = new Trans('en', 'es', [
        'en' => ['app' => []],
        'es' => ['app' => ['title' => 'Application']],
    ]);

    expect($translator->get('app.title'))->toBe('Application');
});

it('has considers primary and fallback', function () {
    $translator = new Trans('en', 'es', [
        'en' => ['blog' => ['post' => 'Post']],
        'es' => ['blog' => ['comment' => 'Comment']],
    ]);

    expect($translator->has('blog.post'))->toBeTrue();
    expect($translator->has('blog.comment'))->toBeTrue();
    expect($translator->has('blog.missing'))->toBeFalse();
});

it('setLocale switches active catalogue', function () {
    $translator = new Trans('en', 'es', [
        'en' => ['ui' => ['yes' => 'Yes']],
        'es' => ['ui' => ['yes' => 'Sí']],
    ]);

    expect($translator->get('ui.yes'))->toBe('Yes');

    $translator->setLocale('es');

    expect($translator->get('ui.yes'))->toBe('Sí');
});

it('works when lang directory does not exist', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (): bool => false,
    );

    $this->app->swap(File::class, $mock);

    expect(Translator::get('users.greeting'))->toBe('users.greeting');
});

it('returns line unchanged when no replacements provided', function () {
    $translator = new Trans('en', 'en', [
        'en' => [
            'users' => [
                'welcome' => 'Welcome, :name',
            ],
        ],
    ]);

    expect($translator->get('users.welcome'))->toBe('Welcome, :name');
});

it('returns line unchanged when replacement is null', function () {
    $translator = new Trans('en', 'en', [
        'en' => [
            'users' => [
                'welcome' => 'Welcome, :name',
            ],
        ],
    ]);

    expect($translator->get('users.welcome', ['name' => null]))->toBe('Welcome, :name');
});

<?php

declare(strict_types=1);

use Phenix\Util\Str;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\UuidV4;

it('creates universal identifiers', function () {
    $uuid = Str::uuid();
    $ulid = Str::ulid();

    expect($uuid)->toBeInstanceOf(UuidV4::class);
    expect($ulid)->toBeInstanceOf(Ulid::class);
});

it('create slugs', function () {
    $slug = Str::slug('Hello World');

    expect($slug)->toBe('hello-world');
});

it('checks if string starts with a prefix', function () {
    $string = Str::start('Hello World', 'Hello');

    expect($string)->toBe('Hello World');
});

it('checks if string ends with a suffix', function () {
    $string = Str::finish('Hello World', 'World');

    expect($string)->toBe('Hello World');
});

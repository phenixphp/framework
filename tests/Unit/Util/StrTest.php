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
    expect(Str::start('World', 'Hello '))->toBe('Hello World');
});

it('checks if string ends with a suffix', function () {
    $string = Str::finish('Hello World', 'World');

    expect($string)->toBe('Hello World');
    expect(Str::finish('Hello', ' World'))->toBe('Hello World');
});

it('generates random string with default length', function (): void {
    $random = Str::random();

    expect(strlen($random))->toBe(16);
});

it('generates random string with custom length', function (): void {
    $length = 32;
    $random = Str::random($length);

    expect(strlen($random))->toBe($length);
});

it('generates different random strings', function (): void {
    $random1 = Str::random(20);
    $random2 = Str::random(20);

    expect($random1 === $random2)->toBeFalse();
});

it('generates random string with only allowed characters', function (): void {
    $random = Str::random(100);

    expect(preg_match('/^[a-zA-Z0-9]+$/', $random))->toBe(1);
});

it('generates single character string', function (): void {
    $random = Str::random(1);

    expect(strlen($random))->toBe(1);
    expect(preg_match('/^[a-zA-Z0-9]$/', $random))->toBe(1);
});

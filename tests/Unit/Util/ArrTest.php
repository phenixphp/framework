<?php

declare(strict_types=1);

use Phenix\Util\Arr;

it('can get a value from an array', function () {
    $array = [
        'name' => 'John',
        'age' => 30,
        'address' => [
            'city' => 'New York',
            'zip' => '10001'
        ]
    ];
    expect(Arr::get($array, 'name'))->toBe('John');
    expect(Arr::get($array, 'age'))->toBe(30);
    expect(Arr::get($array, 'address.city'))->toBe('New York');
    expect(Arr::get($array, 'nonexistent_key'))->toBeNull();
    expect(Arr::get($array, 'nonexistent_key.dotted'))->toBeNull();
});

it('can set a value in an array', function () {
    $array = ['name' => 'John'];
    Arr::set($array, 'age', 30);
    expect($array['age'])->toBe(30);
});

it('can check if an array has a key', function () {
    $array = ['name' => 'John', 'age' => 30];
    expect(Arr::has($array, 'name'))->toBeTrue();
    expect(Arr::has($array, 'nonexistent_key'))->toBeFalse();
});

it('can undot an array', function () {
    $array = [
        'user.name' => 'John',
        'user.age' => 30,
        'address.city' => 'New York',
        'address.zip' => '10001'
    ];
    $expected = [
        'user' => [
            'name' => 'John',
            'age' => 30
        ],
        'address' => [
            'city' => 'New York',
            'zip' => '10001'
        ]
    ];
    expect(Arr::undot($array))->toBe($expected);
});


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
    expect(Arr::get($array, null))->toBe($array);
});

it('can set a value in an array', function () {
    $array = ['name' => 'John'];
    Arr::set($array, 'age', 30);
    expect($array['age'])->toBe(30);
});

it('can check if an array has a key', function () {
    $array = [
        'name' => 'John',
        'age' => 30,
        'address' => [
            'city' => 'New York',
            'zip' => '10001'
        ]
    ];
    expect(Arr::has($array, 'name'))->toBeTrue();
    expect(Arr::has($array, 'nonexistent_key'))->toBeFalse();
    expect(Arr::has($array, []))->toBeFalse();
    expect(Arr::has($array, 'address.city'))->toBeTrue();

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

it('can get the first value from an array', function () {
    $array = [10, 20, 30, 40];
    expect(Arr::first($array))->toBe(10);

    $array = ['name' => 'John', 'age' => 30];
    expect(Arr::first($array))->toBe('John');

    $array = [];
    expect(Arr::first($array))->toBeNull();
});

it('can wrap a value in an array', function () {
    expect(Arr::wrap('John'))->toBe(['John']);
    expect(Arr::wrap(['John']))->toBe(['John']);
    expect(Arr::wrap(null))->toBe([]);
    expect(Arr::wrap(['name' => 'John']))->toBe(['name' => 'John']);
});

it('can check if a key exists in an array', function () {
    $array = ['name' => 'John', 'age' => 30];

    expect(Arr::exists($array, 'name'))->toBeTrue();
    expect(Arr::exists($array, 'nonexistent_key'))->toBeFalse();
    expect(Arr::exists($array, 'age'))->toBeTrue();

    $arrClass = new class implements ArrayAccess {
        private $container = [];

        public function offsetSet($offset, $value): void
        {
            if ($offset === null) {
                $this->container[] = $value;
            } else {
                $this->container[$offset] = $value;
            }
        }

        public function offsetExists($offset): bool
        {
            return isset($this->container[$offset]);
        }

        public function offsetUnset($offset): void
        {
            unset($this->container[$offset]);
        }

        public function offsetGet($offset): string|null
        {
            return isset($this->container[$offset]) ? $this->container[$offset] : null;
        }
    };

    $arrClass['name'] = 'John';

    expect(Arr::exists($arrClass, 'name'))->toBeTrue();
});


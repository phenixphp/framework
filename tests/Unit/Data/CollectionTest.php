<?php

declare(strict_types=1);

use Phenix\Data\Collection;
use Ramsey\Collection\Exception\CollectionMismatchException;
use Ramsey\Collection\Sort;

it('creates collection from array', function () {
    $collection = Collection::fromArray([['name' => 'John']]);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->isEmpty())->toBe(false);
});

it('returns first element', function () {
    $collection = Collection::fromArray([['name' => 'John'], ['name' => 'Jane']]);

    expect($collection->first())->toBe(['name' => 'John']);
});

it('returns null when collection is empty', function () {
    $collection = new Collection('array');

    expect($collection->first())->toBeNull();
});

it('filters items based on callback', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'age' => 25],
        ['name' => 'Jane', 'age' => 30],
        ['name' => 'Bob', 'age' => 20],
    ]);

    $filtered = $collection->filter(fn (array $item) => $item['age'] >= 25);

    expect($filtered)->toBeInstanceOf(Collection::class);
    expect($filtered->count())->toBe(2);
    expect($filtered->first()['name'])->toBe('John');
});

it('filter returns empty collection when no items match', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'age' => 25],
        ['name' => 'Jane', 'age' => 30],
    ]);

    $filtered = $collection->filter(fn (array $item) => $item['age'] > 50);

    expect($filtered)->toBeInstanceOf(Collection::class);
    expect($filtered->isEmpty())->toBe(true);
});

it('filter returns new collection instance', function () {
    $collection = Collection::fromArray([['name' => 'John']]);
    $filtered = $collection->filter(fn (array $item) => true);

    expect($filtered)->toBeInstanceOf(Collection::class);
    expect($filtered)->not()->toBe($collection);
});

it('transforms items based on callback', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'age' => 25],
        ['name' => 'Jane', 'age' => 30],
    ]);

    $mapped = $collection->map(fn (array $item) => $item['name']);

    expect($mapped)->toBeInstanceOf(Collection::class);
    expect($mapped->count())->toBe(2);
    expect($mapped->first())->toBe('John');
});

it('map can transform to different types', function () {
    $collection = Collection::fromArray([1, 2, 3]);
    $mapped = $collection->map(fn (int $num) => ['value' => $num * 2]);

    expect($mapped)->toBeInstanceOf(Collection::class);
    expect($mapped->first())->toBe(['value' => 2]);
});

it('map returns new collection instance', function () {
    $collection = Collection::fromArray([1, 2, 3]);
    $mapped = $collection->map(fn (int $num) => $num);

    expect($mapped)->toBeInstanceOf(Collection::class);
    expect($mapped)->not()->toBe($collection);
});

it('filters by property value using where', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'role' => 'admin'],
        ['name' => 'Jane', 'role' => 'user'],
        ['name' => 'Bob', 'role' => 'admin'],
    ]);

    $admins = $collection->where('role', 'admin');

    expect($admins)->toBeInstanceOf(Collection::class);
    expect($admins->count())->toBe(2);
    expect($admins->first()['name'])->toBe('John');
});

it('where returns empty collection when no matches', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'role' => 'admin'],
        ['name' => 'Jane', 'role' => 'user'],
    ]);

    $guests = $collection->where('role', 'guest');

    expect($guests)->toBeInstanceOf(Collection::class);
    expect($guests->isEmpty())->toBe(true);
});

it('where returns new collection instance', function () {
    $collection = Collection::fromArray([['name' => 'John', 'role' => 'admin']]);
    $filtered = $collection->where('role', 'admin');

    expect($filtered)->toBeInstanceOf(Collection::class);
    expect($filtered)->not()->toBe($collection);
});

it('sorts items by property in ascending order', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
        ['name' => 'Bob', 'age' => 35],
    ]);

    $sorted = $collection->sort('age');

    expect($sorted)->toBeInstanceOf(Collection::class);
    expect($sorted->first()['name'])->toBe('Jane');
    expect($sorted->last()['name'])->toBe('Bob');
});

it('sorts items by property in descending order', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
        ['name' => 'Bob', 'age' => 35],
    ]);

    $sorted = $collection->sort('age', Sort::Descending);

    expect($sorted)->toBeInstanceOf(Collection::class);
    expect($sorted->first()['name'])->toBe('Bob');
    expect($sorted->last()['name'])->toBe('Jane');
});

it('sorts items without property when comparing elements directly', function () {
    $collection = new Collection('integer', [3, 1, 4, 1, 5, 9, 2, 6]);
    $sorted = $collection->sort();

    expect($sorted)->toBeInstanceOf(Collection::class);
    expect($sorted->first())->toBe(1);
    expect($sorted->last())->toBe(9);
});

it('sort returns new collection instance', function () {
    $collection = new Collection('integer', [3, 1, 2]);
    $sorted = $collection->sort();

    expect($sorted)->toBeInstanceOf(Collection::class);
    expect($sorted)->not()->toBe($collection);
});

it('returns divergent items between collections', function () {
    $collection1 = Collection::fromArray([1, 2, 3, 4]);
    $collection2 = Collection::fromArray([3, 4, 5, 6]);

    $diff = $collection1->diff($collection2);

    expect($diff)->toBeInstanceOf(Collection::class);
    expect($diff->count())->toBe(4); // 1, 2, 5, 6
    expect($diff->contains(1))->toBe(true);
    expect($diff->contains(2))->toBe(true);
    expect($diff->contains(5))->toBe(true);
    expect($diff->contains(6))->toBe(true);
});

it('diff returns empty collection when collections are identical', function () {
    $collection1 = Collection::fromArray([1, 2, 3]);
    $collection2 = Collection::fromArray([1, 2, 3]);

    $diff = $collection1->diff($collection2);

    expect($diff)->toBeInstanceOf(Collection::class);
    expect($diff->isEmpty())->toBe(true);
});

it('diff returns new collection instance', function () {
    $collection1 = Collection::fromArray([1, 2, 3]);
    $collection2 = Collection::fromArray([2, 3, 4]);

    $diff = $collection1->diff($collection2);

    expect($diff)->toBeInstanceOf(Collection::class);
    expect($diff)->not()->toBe($collection1);
    expect($diff)->not()->toBe($collection2);
});

// Intersect tests
it('returns intersecting items between collections', function () {
    $collection1 = new Collection('integer', [1, 2, 3, 4]);
    $collection2 = new Collection('integer', [3, 4, 5, 6]);

    $intersect = $collection1->intersect($collection2);

    expect($intersect)->toBeInstanceOf(Collection::class);
    expect($intersect->count())->toBe(2); // 3, 4
    expect($intersect->contains(3))->toBe(true);
    expect($intersect->contains(4))->toBe(true);
});

it('intersect returns empty collection when no intersection exists', function () {
    $collection1 = new Collection('integer', [1, 2, 3]);
    $collection2 = new Collection('integer', [4, 5, 6]);

    $intersect = $collection1->intersect($collection2);

    expect($intersect)->toBeInstanceOf(Collection::class);
    expect($intersect->isEmpty())->toBe(true);
});

it('intersect returns new collection instance', function () {
    $collection1 = new Collection('integer', [1, 2, 3]);
    $collection2 = new Collection('integer', [2, 3, 4]);

    $intersect = $collection1->intersect($collection2);

    expect($intersect)->toBeInstanceOf(Collection::class);
    expect($intersect)->not()->toBe($collection1);
    expect($intersect)->not()->toBe($collection2);
});

it('merges multiple collections', function () {
    $collection1 = Collection::fromArray([1, 2, 3]);
    $collection2 = Collection::fromArray([4, 5]);
    $collection3 = Collection::fromArray([6, 7]);

    $merged = $collection1->merge($collection2, $collection3);

    expect($merged)->toBeInstanceOf(Collection::class);
    expect($merged->count())->toBe(7);
    expect($merged->contains(1))->toBe(true);
    expect($merged->contains(7))->toBe(true);
});

it('merges collections with array keys', function () {
    $collection1 = new Collection('array', ['a' => ['name' => 'John']]);
    $collection2 = new Collection('array', ['b' => ['name' => 'Jane']]);

    $merged = $collection1->merge($collection2);

    expect($merged)->toBeInstanceOf(Collection::class);
    expect($merged->count())->toBe(2);
    expect($merged->offsetExists('a'))->toBe(true);
    expect($merged->offsetExists('b'))->toBe(true);
});

it('merge returns new collection instance', function () {
    $collection1 = Collection::fromArray([1, 2]);
    $collection2 = Collection::fromArray([3, 4]);

    $merged = $collection1->merge($collection2);

    expect($merged)->toBeInstanceOf(Collection::class);
    expect($merged)->not()->toBe($collection1);
    expect($merged)->not()->toBe($collection2);
});

it('merge throws exception when merging incompatible collection types', function () {
    $collection1 = Collection::fromArray([1, 2, 3]);
    $collection2 = new Collection('string', ['a', 'b', 'c']);

    $collection1->merge($collection2);
})->throws(CollectionMismatchException::class);

it('allows fluent method chaining', function () {
    $collection = Collection::fromArray([
        ['name' => 'John', 'age' => 30, 'role' => 'admin'],
        ['name' => 'Jane', 'age' => 25, 'role' => 'user'],
        ['name' => 'Bob', 'age' => 35, 'role' => 'admin'],
        ['name' => 'Alice', 'age' => 28, 'role' => 'user'],
    ]);

    $result = $collection
        ->filter(fn (array $item) => $item['age'] >= 28)
        ->where('role', 'admin')
        ->sort('age', Sort::Descending)
        ->map(fn (array $item) => $item['name']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(2);
    expect($result->first())->toBe('Bob');
});

it('efficiently detects homogeneous array types', function () {
    $largeArray = array_fill(0, 10000, ['key' => 'value']);

    $start = microtime(true);
    $collection = Collection::fromArray($largeArray);
    $duration = microtime(true) - $start;

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->getType())->toBe('array');
    expect($duration)->toBeLessThan(0.5); // Should complete in less than 500ms
});

it('efficiently detects mixed array types', function () {
    $mixedArray = [1, 'string', 3.14, true, ['array']];

    $collection = Collection::fromArray($mixedArray);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->getType())->toBe('mixed');
});

it('handles empty arrays efficiently', function () {
    $collection = Collection::fromArray([]);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->isEmpty())->toBe(true);
    expect($collection->getType())->toBe('mixed');
});

it('detects type from single element', function () {
    $collection = Collection::fromArray([42]);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->getType())->toBe('integer');
    expect($collection->count())->toBe(1);
});

it('stops checking types early when mixed is detected', function () {
    $array = [1, 'two'];
    for ($i = 0; $i < 10000; $i++) {
        $array[] = $i;
    }

    $start = microtime(true);
    $collection = Collection::fromArray($array);
    $duration = microtime(true) - $start;

    expect($collection->getType())->toBe('mixed');
    expect($duration)->toBeLessThan(0.1);
});


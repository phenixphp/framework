<?php

declare(strict_types=1);

use Phenix\Data\Collection;

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

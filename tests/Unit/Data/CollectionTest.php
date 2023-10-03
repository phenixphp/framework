<?php

declare(strict_types=1);

use Phenix\Data\Collection;

it('creates collection from array', function () {
    $collection = Collection::fromArray([['name' => 'John']]);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->isEmpty())->toBe(false);
});

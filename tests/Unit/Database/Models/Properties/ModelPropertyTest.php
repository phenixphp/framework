<?php

declare(strict_types=1);

use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Properties\ModelProperty;
use Phenix\Util\Date;
use Tests\Unit\Database\Models\Properties\Json;

it('resolves property instance', function () {
    $property = new ModelProperty(
        'data',
        Json::class,
        true,
        new Column(name: 'data'),
        '{"name": "John Doe"}'
    );

    expect($property->resolveInstance())->toBeInstanceOf(Json::class);
});

it('gets null when value is nullable', function () {
    $property = new ModelProperty(
        'data',
        '?' . Json::class,
        true,
        new Column(name: 'data'),
        null
    );

    expect($property->resolveInstance())->toBeNull();
    expect($property->getValue())->toBeNull();
});

it('gets null when date is nullable', function () {
    $property = new ModelProperty(
        'date',
        '?' . Date::class,
        true,
        new Column(name: 'date'),
        null
    );

    expect($property->resolveInstance())->toBeNull();
    expect($property->getValue())->toBeNull();
});

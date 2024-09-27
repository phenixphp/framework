<?php

declare(strict_types=1);

use Faker\Generator;
use Phenix\Database\Seed;

it('verifies faker instance in seed class', function () {
    $seeder = new class () extends Seed {};

    $reflection = new ReflectionClass($seeder);

    $property = $reflection->getProperty('faker');
    $property->setAccessible(true);

    $faker = $property->getValue($seeder);

    expect($faker)->toBeInstanceOf(Generator::class);
});

<?php

declare(strict_types=1);

use Phenix\Runtime\Config;

it('can get environment configurations successfully', function () {
    $config = Config::build();

    expect($config->get('app.name'))->toBe('Phenix');
    expect($config->has('app.name'))->toBeTrue();
});

it('can set environment configurations successfully', function () {
    $config = Config::build();

    $config->set('app.name', 'PHPhenix');

    expect($config->get('app.name'))->toBe('PHPhenix');
});

it('retrieve configurations from global config helper function', function (): void {
    config('app.name', 'DefaultApp');

    expect(config('app.name'))->toBe('Phenix');
});

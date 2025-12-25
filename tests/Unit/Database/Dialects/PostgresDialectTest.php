<?php

declare(strict_types=1);

use Phenix\Database\Dialects\PostgreSQL\PostgresDialect;

test('PostgresDialect has correct capabilities', function () {
    $dialect = new PostgresDialect();
    $capabilities = $dialect->capabilities();

    expect($capabilities->supportsLocks)->toBeTrue();
    expect($capabilities->supportsUpsert)->toBeTrue();
    expect($capabilities->supportsReturning)->toBeTrue();
    expect($capabilities->supportsJsonOperators)->toBeTrue();
    expect($capabilities->supportsAdvancedLocks)->toBeTrue();
    expect($capabilities->supportsInsertIgnore)->toBeFalse();
    expect($capabilities->supportsFulltextSearch)->toBeTrue();
    expect($capabilities->supportsGeneratedColumns)->toBeTrue();
});

test('PostgresDialect supports method works correctly', function () {
    $dialect = new PostgresDialect();
    $capabilities = $dialect->capabilities();

    expect($capabilities->supports('locks'))->toBeTrue();
    expect($capabilities->supports('returning'))->toBeTrue();
    expect($capabilities->supports('advancedLocks'))->toBeTrue();
    expect($capabilities->supports('insertIgnore'))->toBeFalse();
});

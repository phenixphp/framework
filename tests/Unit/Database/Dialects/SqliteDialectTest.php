<?php

declare(strict_types=1);

use Phenix\Database\Dialects\SQLite\SqliteDialect;

test('SqliteDialect has correct capabilities', function () {
    $dialect = new SqliteDialect();
    $capabilities = $dialect->capabilities();

    expect($capabilities->supportsLocks)->toBeFalse();
    expect($capabilities->supportsUpsert)->toBeTrue();
    expect($capabilities->supportsReturning)->toBeTrue();
    expect($capabilities->supportsJsonOperators)->toBeTrue();
    expect($capabilities->supportsAdvancedLocks)->toBeFalse();
    expect($capabilities->supportsInsertIgnore)->toBeTrue();
    expect($capabilities->supportsFulltextSearch)->toBeTrue();
    expect($capabilities->supportsGeneratedColumns)->toBeTrue();
});

test('SqliteDialect supports method works correctly', function () {
    $dialect = new SqliteDialect();
    $capabilities = $dialect->capabilities();

    expect($capabilities->supports('locks'))->toBeFalse();
    expect($capabilities->supports('upsert'))->toBeTrue();
    expect($capabilities->supports('returning'))->toBeTrue();
    expect($capabilities->supports('advancedLocks'))->toBeFalse();
});

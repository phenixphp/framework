<?php

declare(strict_types=1);

use Phenix\Database\Dialects\MySQL\MysqlDialect;

test('MysqlDialect has correct capabilities', function () {
    $dialect = new MysqlDialect();
    $capabilities = $dialect->capabilities();

    expect($capabilities->supportsLocks)->toBeTrue();
    expect($capabilities->supportsUpsert)->toBeTrue();
    expect($capabilities->supportsReturning)->toBeFalse();
    expect($capabilities->supportsJsonOperators)->toBeTrue();
    expect($capabilities->supportsAdvancedLocks)->toBeFalse();
    expect($capabilities->supportsInsertIgnore)->toBeTrue();
    expect($capabilities->supportsFulltextSearch)->toBeTrue();
    expect($capabilities->supportsGeneratedColumns)->toBeTrue();
});

test('MysqlDialect supports method works correctly', function () {
    $dialect = new MysqlDialect();
    $capabilities = $dialect->capabilities();

    expect($capabilities->supports('locks'))->toBeTrue();
    expect($capabilities->supports('upsert'))->toBeTrue();
    expect($capabilities->supports('returning'))->toBeFalse();
    expect($capabilities->supports('advancedLocks'))->toBeFalse();
});

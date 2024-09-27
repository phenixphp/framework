<?php

declare(strict_types=1);

use Phenix\Util\Str;
use Phenix\Validation\Rules\Ulid;
use Phenix\Validation\Rules\Uuid;
use Phenix\Validation\Types\Uid;

it('runs validation to check if string is a valid UUID', function (array $data, bool $expected) {
    $rules = Uid::required()->uuid()->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof Uuid) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid UUID' => [['value' => (string) Str::uuid()], true],
    'invalid UUID' => [['value' => 'abc-123'], false],
]);

it('runs validation to check if string is a valid ULID', function (array $data, bool $expected) {
    $rules = Uid::required()->ulid()->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof Ulid) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid ULID' => [['value' => (string) Str::ulid()], true],
    'invalid ULID' => [['value' => 'abc-123'], false],
]);

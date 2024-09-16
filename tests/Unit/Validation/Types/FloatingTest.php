<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Between;
use Phenix\Validation\Rules\In;
use Phenix\Validation\Rules\Max;
use Phenix\Validation\Rules\Min;
use Phenix\Validation\Rules\NotIn;
use Phenix\Validation\Rules\Numbers\DecimalDigits;
use Phenix\Validation\Rules\Numbers\DecimalDigitsBetween;
use Phenix\Validation\Rules\Numbers\FloatingDigits;
use Phenix\Validation\Rules\Numbers\FloatingDigitsBetween;
use Phenix\Validation\Types\Floating;

it('runs validation with required decimal data', function (array $data, bool $expected) {
    $rules = Floating::required()->toArray();

    [$requiredRule, $strRule] = $rules['type'];

    $requiredRule->setField('value');
    $requiredRule->setData($data);

    expect($requiredRule->passes())->toBeTruthy();

    $strRule->setField('value');
    $strRule->setData($data);

    expect($strRule->passes())->toBe($expected);
})->with([
    'decimal value' => [['value' => 1.1], true],
    'negative decimal value' => [['value' => -1.1], true],
    'zero decimal value' => [['value' => 0.0], true],
    'integer value' => [['value' => 1], false],
    'negative integer value' => [['value' => -1], false],
    'zero integer value' => [['value' => 0], false],
    'string value' => [['value' => '1.1'], false],
    'array value' => [['value' => [1.1]], false],
    'bool value' => [['value' => true], false],
]);

it('runs validation with minimum decimal allowed', function (array $data, bool $expected) {
    $rules = Floating::required()->min(5.0)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof Min) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid decimal value' => [['value' => 5.1], true],
    'valid value with minimum decimal allowed' => [['value' => 5.0], true],
    'invalid decimal value' => [['value' => 4.9], false],
]);

it('runs validation with maximum allowed', function (array $data, bool $expected) {
    $rules = Floating::required()->max(5.0)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof Max) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid decimal value' => [['value' => 4.9], true],
    'valid value with maximum decimal allowed' => [['value' => 5.0], true],
    'invalid decimal value' => [['value' => 5.1], false],
]);

it('runs validation with between allowed decimal values', function (array $data, bool $expected) {
    $rules = Floating::required()->between(5.0, 10.0)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof Between) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid value' => [['value' => 7.0], true],
    'valid value with minimum allowed' => [['value' => 5.0], true],
    'valid value with maximum allowed' => [['value' => 10.0], true],
    'invalid value exceeds minimum allowed' => [['value' => 4.0], false],
    'invalid value exceeds maximum allowed' => [['value' => 11.0], false],
]);

it('runs validation for allowed values in list', function (array $data, bool $expected) {
    $rules = Floating::required()->in([1.0, 2.0])->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof In) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'allowed values' => [['value' => 1.0], true],
    'invalid allowed values' => [['value' => 3.0], false],
]);

it('runs validation for not allowed values in list', function (array $data, bool $expected) {
    $rules = Floating::required()->notIn([1.0, 2.0])->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof NotIn) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'not allowed values' => [['value' => 3.0], true],
    'invalid not allowed values' => [['value' => 1.0], false],
]);

it('runs validation with floating digits length', function (array $data, bool $expected) {
    $rules = Floating::required()->digits(3)->decimals(1)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof FloatingDigits) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid length' => [['value' => 123.1], true],
    'invalid short length' => [['value' => 12.1], false],
    'invalid long length' => [['value' => 1234.1], false],
]);

it('runs validation with floating decimal digits length', function (array $data, bool $expected) {
    $rules = Floating::required()->digits(2)->decimals(3)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof DecimalDigits) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid length' => [['value' => 12.456], true],
    'invalid short length' => [['value' => 12.12], false],
    'invalid long length' => [['value' => 12.3456], false],
]);

it('runs validation with floating digits length range', function (array $data, bool $expected) {
    $rules = Floating::required()->digitsBetween(2, 4)->decimals(1)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof FloatingDigitsBetween) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid length' => [['value' => 123.4], true],
    'valid minimum length' => [['value' => 12.3], true],
    'valid maximum length' => [['value' => 1234.5], true],
    'invalid short length' => [['value' => 1.1], false],
    'invalid long length' => [['value' => 12345.1], false],
]);

it('runs validation with floating decimals length range', function (array $data, bool $expected) {
    $rules = Floating::required()->digitsBetween(2, 4)->decimalsBetween(2, 4)->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof DecimalDigitsBetween) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid length' => [['value' => 123.422], true],
    'valid minimum length' => [['value' => 123.33], true],
    'valid maximum length' => [['value' => 123.5678], true],
    'invalid short length' => [['value' => 123.1], false],
    'invalid long length' => [['value' => 123.45678], false],
]);

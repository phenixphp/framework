<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Min;

it('checks minimum values according to data type', function (
    float|int $limit,
    string $field,
    array $data,
    bool $expected
): void {
    $rule = new Min($limit);
    $rule->setField($field);
    $rule->setData($data);

    expect($rule->passes())->toBe($expected);
})->with([
    'integer value' => [1, 'value', ['value' => 1], true],
    'invalid integer value' => [1, 'value', ['value' => 0], false],
    'float value' => [1.0, 'value', ['value' => 1.0], true],
    'invalid float value' => [1.0, 'value', ['value' => 0], false],
    'string value' => [3, 'name', ['name' => 'John'], true],
    'invalid string value' => [4, 'name', ['name' => 'Joe'], false],
    'array value' => [2, 'customer', ['customer' => ['name' => 'John', 'last_name' => 'Doe']], true],
    'invalid array value' => [2, 'customer', ['customer' => ['name' => 'John']], false],
    'object value' => [
        2,
        'customer',
        ['customer' => new class () implements Countable {
            public function count(): int
            {
                return 2;
            }
        }],
        true,
    ],
    'invalid object value' => [
        2,
        'customer',
        ['customer' => new class () implements Countable {
            public function count(): int
            {
                return 1;
            }
        }],
        false,
    ],
]);

it('builds proper min messages for each type', function (int|float $limit, string $field, array $data, string $expectedFragment): void {
    $rule = new Min($limit);
    $rule->setField($field)->setData($data);

    expect($rule->passes())->toBeFalse();

    $message = $rule->message();

    expect($message)->toBeString();
    expect($message)->toContain($expectedFragment);
})->with([
    'numeric' => [3, 'value', ['value' => 2], 'The value must be at least 3'],
    'string' => [5, 'name', ['name' => 'John'], 'The name must be at least 5 characters'],
    'array' => [3, 'items', ['items' => ['a','b']], 'The items must have at least 3 items'],
]);

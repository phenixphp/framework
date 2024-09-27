<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Max;

it('checks maximum values according to data type', function (
    float|int $limit,
    string $field,
    array $data,
    bool $expected
): void {
    $rule = new Max($limit);
    $rule->setField($field);
    $rule->setData($data);

    expect($rule->passes())->toBe($expected);
})->with([
    'integer value' => [1, 'value', ['value' => 1], true],
    'invalid integer value' => [1, 'value', ['value' => 2], false],
    'float value' => [1.0, 'value', ['value' => 1.0], true],
    'invalid float value' => [1.0, 'value', ['value' => 2.0], false],
    'string value' => [4, 'name', ['name' => 'John'], true],
    'invalid string value' => [3, 'name', ['name' => 'John'], false],
    'array value' => [2, 'customer', ['customer' => ['name' => 'John', 'last_name' => 'Doe']], true],
    'invalid array value' => [1, 'customer', ['customer' => ['name' => 'John', 'last_name' => 'Doe']], false],
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
                return 3;
            }
        }],
        false,
    ],
]);

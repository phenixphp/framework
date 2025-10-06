<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Size;

it('fails size for string length mismatch', function () {
    $rule = new Size(5);
    $rule->setField('name')->setData(['name' => 'John']);

    assertFalse($rule->passes());
    assertStringContainsString('must be 5 characters', (string) $rule->message());
});

it('passes size for exact string length', function () {
    $rule = new Size(4);
    $rule->setField('name')->setData(['name' => 'John']);

    assertTrue($rule->passes());
});

it('checks size according to data type', function (
    float|int $limit,
    string $field,
    array $data,
    bool $expected
): void {
    $rule = new Size($limit);
    $rule->setField($field);
    $rule->setData($data);

    expect($rule->passes())->toBe($expected);
})->with([
    'integer value' => [1, 'value', ['value' => 1], true],
    'invalid integer value' => [1, 'value', ['value' => 0], false],
    'float value' => [1.0, 'value', ['value' => 1.0], true],
    'invalid float value' => [1.0, 'value', ['value' => 0], false],
    'string value' => [4, 'name', ['name' => 'John'], true],
    'invalid string value' => [3, 'name', ['name' => 'John'], false],
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


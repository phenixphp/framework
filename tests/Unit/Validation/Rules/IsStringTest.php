<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsString;

it('fails when value not a string', function () {
    $rule = new IsString();
    $rule->setField('name')->setData(['name' => 123]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a string', (string) $rule->message());
});

it('passes when value is a string', function () {
    $rule = new IsString();
    $rule->setField('name')->setData(['name' => 'John']);

    assertTrue($rule->passes());
});

it('display field name for humans', function () {
    $rule = new IsString();
    $rule->setField('last_name')->setData(['last_name' => 123]);

    assertFalse($rule->passes());
    assertStringContainsString('last name must be a string', (string) $rule->message());
});

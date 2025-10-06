<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsString;

it('fails is_string when value not a string', function () {
    $rule = new IsString();
    $rule->setField('name')->setData(['name' => 123]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a string', (string) $rule->message());
});

it('passes is_string when value is a string', function () {
    $rule = new IsString();
    $rule->setField('name')->setData(['name' => 'John']);

    assertTrue($rule->passes());
});

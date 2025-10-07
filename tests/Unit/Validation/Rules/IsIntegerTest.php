<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Numbers\IsInteger;

it('fails is_integer when value not integer', function () {
    $rule = new IsInteger();
    $rule->setField('age')->setData(['age' => '12']);

    assertFalse($rule->passes());
    assertStringContainsString('must be an integer', (string) $rule->message());
});

it('passes is_integer when value integer', function () {
    $rule = new IsInteger();
    $rule->setField('age')->setData(['age' => 12]);

    assertTrue($rule->passes());
});

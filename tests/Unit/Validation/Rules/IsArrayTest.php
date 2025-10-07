<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsArray;

it('fails is_array when value not array', function () {
    $rule = new IsArray();
    $rule->setField('data')->setData(['data' => 'string']);

    assertFalse($rule->passes());
    assertStringContainsString('must be an array', (string) $rule->message());
});

it('passes is_array when value is array', function () {
    $rule = new IsArray();
    $rule->setField('data')->setData(['data' => []]);

    assertTrue($rule->passes());
});

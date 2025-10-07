<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Numbers\Digits;

it('fails when value digits length does not match required', function () {
    $rule = new Digits(3);
    $rule->setField('code')->setData(['code' => 12]); // length 2

    assertFalse($rule->passes());
    assertStringContainsString('must be 3 digits', (string) $rule->message());
});

it('passes when value digits length matches required', function () {
    $rule = new Digits(3);
    $rule->setField('code')->setData(['code' => 123]);

    assertTrue($rule->passes());
});

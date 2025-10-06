<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Numbers\IsNumeric;

it('fails is_numeric when value not numeric', function () {
    $rule = new IsNumeric();
    $rule->setField('code')->setData(['code' => 'abc']);

    assertFalse($rule->passes());
    assertStringContainsString('must be a number', (string) $rule->message());
});

it('passes is_numeric when value numeric', function () {
    $rule = new IsNumeric();
    $rule->setField('code')->setData(['code' => '123']);

    assertTrue($rule->passes());
});

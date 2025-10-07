<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Numbers\IsFloat;

it('fails is_float when value not float', function () {
    $rule = new IsFloat();
    $rule->setField('ratio')->setData(['ratio' => 10]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a float', (string) $rule->message());
});

it('passes is_float when value float', function () {
    $rule = new IsFloat();
    $rule->setField('ratio')->setData(['ratio' => 10.5]);

    assertTrue($rule->passes());
});

<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsBool;

it('fails is_bool when value not boolean', function () {
    $rule = new IsBool();
    $rule->setField('flag')->setData(['flag' => 'nope']);

    assertFalse($rule->passes());
    assertStringContainsString('must be true or false', (string) $rule->message());
});

it('passes is_bool when value boolean', function () {
    $rule = new IsBool();
    $rule->setField('flag')->setData(['flag' => true]);

    assertTrue($rule->passes());
});

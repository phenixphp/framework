<?php

declare(strict_types=1);

use Phenix\Validation\Rules\RegEx;

it('fails when value does not match regex', function () {
    $rule = new RegEx('/^[0-9]+$/');
    $rule->setField('code')->setData(['code' => 'abc']);

    assertFalse($rule->passes());
    assertStringContainsString('format is invalid', (string) $rule->message());
});

it('passes when value matches regex', function () {
    $rule = new RegEx('/^[0-9]+$/');
    $rule->setField('code')->setData(['code' => '123']);

    assertTrue($rule->passes());
});

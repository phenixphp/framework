<?php

declare(strict_types=1);

use Phenix\Validation\Rules\EndsWith;

it('fails when string does not end with needle', function () {
    $rule = new EndsWith('suf');
    $rule->setField('text')->setData(['text' => 'prefix']);

    assertFalse($rule->passes());
    assertStringContainsString('must end with', (string) $rule->message());
});

it('passes when string ends with needle', function () {
    $rule = new EndsWith('suf');
    $rule->setField('text')->setData(['text' => 'endsuf']);

    assertTrue($rule->passes());
});

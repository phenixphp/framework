<?php

declare(strict_types=1);

use Phenix\Validation\Rules\In;

it('fails when value not in allowed list', function () {
    $rule = new In(['a','b']);
    $rule->setField('val')->setData(['val' => 'c']);

    assertFalse($rule->passes());
    assertStringContainsString('Allowed', (string) $rule->message());
});

it('passes when value is in allowed list', function () {
    $rule = new In(['a','b']);
    $rule->setField('val')->setData(['val' => 'a']);

    assertTrue($rule->passes());
});

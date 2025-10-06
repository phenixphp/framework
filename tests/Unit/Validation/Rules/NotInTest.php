<?php

declare(strict_types=1);

use Phenix\Validation\Rules\NotIn;

it('fails when value is inside the forbidden list', function () {
    $rule = new NotIn(['a','b','c']);
    $rule->setField('val')->setData(['val' => 'b']);

    assertFalse($rule->passes());
    assertStringContainsString('Disallowed', (string) $rule->message());
});

it('passes when value is not inside the forbidden list', function () {
    $rule = new NotIn(['a','b','c']);
    $rule->setField('val')->setData(['val' => 'x']);

    assertTrue($rule->passes());
});

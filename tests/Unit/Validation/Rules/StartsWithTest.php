<?php

declare(strict_types=1);

use Phenix\Validation\Rules\StartsWith;

it('fails when string does not start with needle', function () {
    $rule = new StartsWith('pre');
    $rule->setField('text')->setData(['text' => 'postfix']);

    assertFalse($rule->passes());
    assertStringContainsString('must start with', (string) $rule->message());
});

it('passes when string starts with needle', function () {
    $rule = new StartsWith('pre');
    $rule->setField('text')->setData(['text' => 'prefix']);

    assertTrue($rule->passes());
});

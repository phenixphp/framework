<?php

declare(strict_types=1);

use Phenix\Validation\Rules\DoesNotStartWith;

it('fails when string starts with forbidden prefix', function () {
    $rule = new DoesNotStartWith('pre');
    $rule->setField('text')->setData(['text' => 'prefix']);

    assertFalse($rule->passes());
    assertStringContainsString('must not start', (string) $rule->message());
});

it('passes when string does not start with forbidden prefix', function () {
    $rule = new DoesNotStartWith('pre');
    $rule->setField('text')->setData(['text' => 'xpre']);

    assertTrue($rule->passes());
});

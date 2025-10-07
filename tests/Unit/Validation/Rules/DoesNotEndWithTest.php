<?php

declare(strict_types=1);

use Phenix\Validation\Rules\DoesNotEndWith;

it('fails when string ends with forbidden suffix', function () {
    $rule = new DoesNotEndWith('suf');
    $rule->setField('text')->setData(['text' => 'endsuf']);

    assertFalse($rule->passes());
    assertStringContainsString('must not end', (string) $rule->message());
});

it('passes when string does not end with forbidden suffix', function () {
    $rule = new DoesNotEndWith('suf');
    $rule->setField('text')->setData(['text' => 'suffixx']);

    assertTrue($rule->passes());
});

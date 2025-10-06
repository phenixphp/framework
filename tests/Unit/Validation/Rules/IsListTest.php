<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsList;

it('passes for a scalar-only list array', function () {
    $rule = new IsList();
    $rule->setField('items')->setData(['items' => ['a', 'b', 'c']]);

    assertTrue($rule->passes());
});

it('fails for non list array (associative)', function () {
    $rule = new IsList();
    $rule->setField('items')->setData(['items' => ['a' => 'value', 'b' => 'v']]);

    assertFalse($rule->passes());
    assertStringContainsString('validation.list', (string) $rule->message());
});

it('fails when list contains non scalar values', function () {
    $rule = new IsList();
    $rule->setField('items')->setData(['items' => ['a', ['nested']]]);

    assertFalse($rule->passes());
});

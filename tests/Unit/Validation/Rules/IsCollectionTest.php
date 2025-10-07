<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsCollection;

it('passes for a list array with at least one non scalar entry', function () {
    $rule = new IsCollection();
    $rule->setField('items')->setData(['items' => ['a', ['nested' => 'value']]]);

    assertTrue($rule->passes());
});

it('fails for scalar-only list (should be a list, not collection)', function () {
    $rule = new IsCollection();
    $rule->setField('items')->setData(['items' => ['a', 'b', 'c']]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a collection', (string) $rule->message());
});

it('fails for associative array where not list', function () {
    $rule = new IsCollection();
    $rule->setField('items')->setData(['items' => ['a' => 'v', 'b' => 'z']]);

    assertFalse($rule->passes());
});

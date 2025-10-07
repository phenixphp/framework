<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Between;

it('fails between for array outside range', function () {
    $rule = new Between(2, 4);
    $rule->setField('items')->setData(['items' => ['a','b','c','d','e']]);

    assertFalse($rule->passes());
    assertStringContainsString('between 2 and 4 items', (string) $rule->message());
});

it('passes between for array inside range', function () {
    $rule = new Between(2, 4);
    $rule->setField('items')->setData(['items' => ['a','b','c']]);

    assertTrue($rule->passes());
});

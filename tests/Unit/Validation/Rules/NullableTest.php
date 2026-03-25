<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Nullable;

it('nullable returns null message and fails when field is missing', function () {
    $rule = new Nullable();
    $rule->setField('foo')->setData([]);

    assertFalse($rule->passes());
    assertSame(null, $rule->message());
});

it('nullable passes and returns null message when value is null', function () {
    $rule = new Nullable();
    $rule->setField('foo')->setData(['foo' => null]);

    assertTrue($rule->passes());
    assertSame(null, $rule->message());
});

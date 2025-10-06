<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Optional;

it('optional skips validation when field missing', function () {
    $rule = new Optional();
    $rule->setField('foo')->setData([]);

    assertTrue($rule->passes());
    assertSame(null, $rule->message());
});

it('optional fails when present but empty', function () {
    $rule = new Optional();
    $rule->setField('foo')->setData(['foo' => '']);

    assertFalse($rule->passes());
    assertSame(null, $rule->message());
});

<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Numbers\DigitsBetween;

it('fails when digits count is below minimum', function () {
    $rule = new DigitsBetween(3, 5);
    $rule->setField('value')->setData(['value' => 12]); // 2 digits

    assertFalse($rule->passes());
    assertStringContainsString('must be between 3 and 5 digits', (string) $rule->message());
});

it('fails when digits count is above maximum', function () {
    $rule = new DigitsBetween(3, 5);
    $rule->setField('value')->setData(['value' => 123456]); // 6 digits

    assertFalse($rule->passes());
});

it('passes when digits count is within range', function () {
    $rule = new DigitsBetween(3, 5);
    $rule->setField('value')->setData(['value' => 1234]); // 4 digits

    assertTrue($rule->passes());
});

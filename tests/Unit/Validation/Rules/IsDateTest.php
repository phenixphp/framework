<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\IsDate;

it('fails for invalid date string', function () {
    $rule = new IsDate();
    $rule->setField('start')->setData(['start' => 'not-date']);

    assertFalse($rule->passes());
    assertStringContainsString('not a valid date', (string) $rule->message());
});

it('passes for valid date string', function () {
    $rule = new IsDate();
    $rule->setField('start')->setData(['start' => '2024-12-01']);

    assertTrue($rule->passes());
});

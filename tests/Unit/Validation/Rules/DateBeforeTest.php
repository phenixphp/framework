<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\Before;

it('fails when date is not before given date', function () {
    $rule = new Before('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-01']);

    assertFalse($rule->passes());
    assertStringContainsString('The date must be a date before the specified date.', (string) $rule->message());
});

it('passes when date is before given date', function () {
    $rule = new Before('2024-01-01');
    $rule->setField('date')->setData(['date' => '2023-12-31']);

    assertTrue($rule->passes());
});

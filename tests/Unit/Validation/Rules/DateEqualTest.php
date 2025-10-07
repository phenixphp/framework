<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\Equal;

it('fails when date is not equal to given date', function () {
    $rule = new Equal('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-02']);

    assertFalse($rule->passes());
    assertStringContainsString('The date must be a date equal to the specified date.', (string) $rule->message());
});

it('passes when date is equal to given date', function () {
    $rule = new Equal('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-01']);

    assertTrue($rule->passes());
});

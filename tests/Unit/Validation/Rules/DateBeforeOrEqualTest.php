<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\BeforeOrEqual;

it('fails when date is not before or equal to given date', function () {
    $rule = new BeforeOrEqual('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-02']);

    assertFalse($rule->passes());
    assertStringContainsString('The date must be a date before or equal to the specified date.', (string) $rule->message());
});

it('passes when date is equal to given date', function () {
    $rule = new BeforeOrEqual('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-01']);

    assertTrue($rule->passes());
});

it('passes when date is before given date', function () {
    $rule = new BeforeOrEqual('2024-01-01');
    $rule->setField('date')->setData(['date' => '2023-12-31']);

    assertTrue($rule->passes());
});

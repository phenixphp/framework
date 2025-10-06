<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\AfterOrEqual;

it('fails when date is not after or equal to date', function () {
    $rule = new AfterOrEqual('2024-01-01');
    $rule->setField('date')->setData(['date' => '2023-12-31']);

    assertFalse($rule->passes());
    assertStringContainsString('validation.date.after_or_equal', (string) $rule->message());
});

it('passes when date is equal', function () {
    $rule = new AfterOrEqual('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-01']);

    assertTrue($rule->passes());
});

it('passes when date is after', function () {
    $rule = new AfterOrEqual('2024-01-01');
    $rule->setField('date')->setData(['date' => '2024-01-02']);

    assertTrue($rule->passes());
});

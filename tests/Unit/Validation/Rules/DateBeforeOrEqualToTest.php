<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\BeforeOrEqualTo;

it('fails when date is not before or equal to related date', function () {
    $rule = new BeforeOrEqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-03',
        'end_date' => '2024-01-02',
    ]);

    assertFalse($rule->passes());
    assertStringContainsString('validation.date.before_or_equal_to', (string) $rule->message());
});

it('passes when date is equal to related date', function () {
    $rule = new BeforeOrEqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-02',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

it('passes when date is before related date', function () {
    $rule = new BeforeOrEqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

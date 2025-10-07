<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\AfterOrEqualTo;

it('fails when date is not after or equal to related date', function () {
    $rule = new AfterOrEqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-02',
    ]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a date after or equal to end_date', (string) $rule->message());
});

it('passes when date is equal to related date', function () {
    $rule = new AfterOrEqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-02',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

it('passes when date is after related date', function () {
    $rule = new AfterOrEqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-03',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

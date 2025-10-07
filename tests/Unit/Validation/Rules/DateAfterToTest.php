<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\AfterTo;

it('fails when date is not strictly after related date', function () {
    $rule = new AfterTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-02',
        'end_date' => '2024-01-02',
    ]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a date after end_date', (string) $rule->message());
});

it('passes when date is after related date', function () {
    $rule = new AfterTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-03',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

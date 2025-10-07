<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\BeforeTo;

it('fails when date is not strictly before related date', function () {
    $rule = new BeforeTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-02',
        'end_date' => '2024-01-01',
    ]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a date before end_date', (string) $rule->message());
});

it('passes when date is before related date', function () {
    $rule = new BeforeTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

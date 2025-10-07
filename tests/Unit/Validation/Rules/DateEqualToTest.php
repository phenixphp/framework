<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\EqualTo;

it('fails when date is not equal to related date', function () {
    $rule = new EqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-02',
    ]);

    assertFalse($rule->passes());
    assertStringContainsString('must be a date equal to end_date', (string) $rule->message());
});

it('passes when date is equal to related date', function () {
    $rule = new EqualTo('end_date');
    $rule->setField('start_date')->setData([
        'start_date' => '2024-01-02',
        'end_date' => '2024-01-02',
    ]);

    assertTrue($rule->passes());
});

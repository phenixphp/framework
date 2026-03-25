<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\After;

it('fails when date is after to date', function () {
    $rule = new After('2024-01-01');
    $rule->setField('date')->setData(['date' => '2023-12-31']);

    assertFalse($rule->passes());
    assertStringContainsString('must be a date after', (string) $rule->message());
});

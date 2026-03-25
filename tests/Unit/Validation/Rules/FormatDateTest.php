<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Dates\Format;

it('fails when date does not match expected format', function () {
    $rule = new Format('Y-m-d');
    $rule->setField('start')->setData(['start' => '2024/01/01']);

    assertFalse($rule->passes());
    assertStringContainsString('does not match the format', (string) $rule->message());
});

it('passes when date matches expected format', function () {
    $rule = new Format('Y-m-d');
    $rule->setField('start')->setData(['start' => '2024-01-01']);

    assertTrue($rule->passes());
});

<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsEmail;

it('fails is_email when invalid email', function () {
    $rule = new IsEmail();
    $rule->setField('email')->setData(['email' => 'invalid']);

    assertFalse($rule->passes());
    assertStringContainsString('valid email', (string) $rule->message());
});

it('passes is_email when valid', function () {
    $rule = new IsEmail();
    $rule->setField('email')->setData(['email' => 'user@example.com']);

    assertTrue($rule->passes());
});

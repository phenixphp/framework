<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsUrl;

it('fails when invalid url', function () {
    $rule = new IsUrl();
    $rule->setField('site')->setData(['site' => 'notaurl']);

    assertFalse($rule->passes());
    assertStringContainsString('valid URL', (string) $rule->message());
});

it('passes when valid', function () {
    $rule = new IsUrl();
    $rule->setField('site')->setData(['site' => 'https://example.com']);

    assertTrue($rule->passes());
});

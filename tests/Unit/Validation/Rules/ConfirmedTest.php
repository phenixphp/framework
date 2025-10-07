<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Confirmed;

it('fails when confirmation does not match', function () {
    $rule = new Confirmed('password_confirmation');
    $rule->setField('password')->setData([
        'password' => 'secret1',
        'password_confirmation' => 'secret2',
    ]);

    assertFalse($rule->passes());
    assertStringContainsString('does not match', (string) $rule->message());
});

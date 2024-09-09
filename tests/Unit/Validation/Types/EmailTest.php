<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsEmail;
use Phenix\Validation\Types\Email;

it('runs validation for emails with default validators', function (array $data, bool $expected) {
    $rules = Email::required()->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('email');
        $rule->setData($data);

        if ($rule instanceof IsEmail) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid email' => [['email' => 'john.doe@gmail.com'], true],
    'invalid email' => [['email' => 'john.doe.gmail.com'], false],
]);

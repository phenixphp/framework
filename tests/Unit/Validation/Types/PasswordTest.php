<?php

declare(strict_types=1);

use Phenix\Validation\Types\Password;
use Phenix\Validation\Validator;

it('validates a secure password successfully', function (): void {
    $password = 'StrongP@ssw0rd!!';

    $validator = (new Validator())
        ->setData([
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->setRules([
            'password' => Password::required()->secure()->confirmed(),
        ]);

    expect($validator->passes())->toBeTrue();
});

it('fails when password confirmation does not match', function (): void {
    $password = 'StrongP@ssw0rd!!';
    $validator = (new Validator())
        ->setData([
            'password' => $password,
            'password_confirmation' => 'WrongP@ssw0rd!!',
        ])
        ->setRules([
            'password' => Password::required()->secure()->confirmed(),
        ]);

    expect($validator->fails())->toBeTrue();
    expect(array_keys($validator->failing()))->toContain('password');
});

it('can disable secure defaults', function (): void {
    $validator = (new Validator())
        ->setData([
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])
        ->setRules([
            'password' => Password::required()->secure(false)->confirmed(),
        ]);

    expect($validator->passes())->toBeTrue();
});

it('accepts custom secure closure', function (): void {
    $validator = (new Validator())
        ->setData([
            'password' => 'abcd1234EFGH',
            'password_confirmation' => 'abcd1234EFGH',
        ])
        ->setRules([
            'password' => Password::required()->secure(fn (): bool => false)->confirmed(),
        ]);

    expect($validator->passes())->toBeTrue();
});

it('fails when password does not meet default secure regex', function (): void {
    $pwd = 'alllowercasepassword';
    $validator = (new Validator())
        ->setData([
            'password' => $pwd,
            'password_confirmation' => $pwd,
        ])
        ->setRules([
            'password' => Password::required()->secure()->confirmed(),
        ]);

    expect($validator->fails())->toBeTrue();
});

it('fails when confirmation field missing', function (): void {
    $password = 'StrongP@ssw0rd!!';
    $validator = (new Validator())
        ->setData([
            'password' => $password,
        ])
        ->setRules([
            'password' => Password::required()->secure()->confirmed(),
        ]);

    expect($validator->fails())->toBeTrue();
});

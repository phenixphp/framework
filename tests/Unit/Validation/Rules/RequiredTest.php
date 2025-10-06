<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Required;

it('fails with required message when value missing', function (): void {
    $rule = new Required();
    $rule->setField('name')->setData([]);

    expect($rule->passes())->toBeFalse();
    expect($rule->message())->toBe('The name field is required.');
});

it('passes required when value present', function (): void {
    $rule = new Required();
    $rule->setField('name')->setData(['name' => 'John']);

    expect($rule->passes())->toBeTrue();
});

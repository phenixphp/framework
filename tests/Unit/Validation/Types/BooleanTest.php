<?php

declare(strict_types=1);

use Phenix\Validation\Types\Boolean;

it('runs validation with required boolean data', function (array $data, bool $expected) {
    $rules = Boolean::required()->toArray();

    [$requiredRule, $boolRule] = $rules['type'];

    $requiredRule->setField('accepted');
    $requiredRule->setData($data);

    expect($requiredRule->passes())->toBeTruthy();

    $boolRule->setField('accepted');
    $boolRule->setData($data);

    expect($boolRule->passes())->toBe($expected);
})->with([
    'accepted field' => [['accepted' => true], true],
    'no accepted field' => [['accepted' => false], true],
    'accepted field with true as string' => [['accepted' => 'true'], true],
    'no accepted field with false as string' => [['accepted' => 'false'], true],
    'accepted field with number' => [['accepted' => 1], true],
    'no accepted field with number' => [['accepted' => 0], true],
    'accepted field with numeric value' => [['accepted' => '1'], true],
    'no accepted field with numeric value' => [['accepted' => '0'], true],
    'invalid accepted field' => [['accepted' => 'truthy'], false],
    'invalid no accepted field' => [['accepted' => 'falsy'], false],
    'invalid accepted field with number' => [['accepted' => 2], false],
    'invalid no accepted field with number' => [['accepted' => -1], false],
]);

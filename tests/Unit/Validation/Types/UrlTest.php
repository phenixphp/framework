<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsUrl;
use Phenix\Validation\Types\Url;

it('runs validation for urls', function (array $data, bool $expected) {
    $rules = Url::required()->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('value');
        $rule->setData($data);

        if ($rule instanceof IsUrl) {
            expect($rule->passes())->toBe($expected);
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
})->with([
    'valid url' => [['value' => 'http://php.net'], true],
    'invalid url' => [['value' => 'http//php.net'], false],
]);

<?php

declare(strict_types=1);

use Phenix\Util\Date as Dates;
use Phenix\Validation\Exceptions\InvalidCollectionDefinition;
use Phenix\Validation\Exceptions\InvalidData;
use Phenix\Validation\Exceptions\InvalidDictionaryDefinition;
use Phenix\Validation\Rules\IsDictionary;
use Phenix\Validation\Rules\IsString;
use Phenix\Validation\Rules\Required;
use Phenix\Validation\Types\Arr;
use Phenix\Validation\Types\ArrList;
use Phenix\Validation\Types\Collection;
use Phenix\Validation\Types\Date;
use Phenix\Validation\Types\Dictionary;
use Phenix\Validation\Types\Str;
use Phenix\Validation\Validator;

it('runs successfully validation with scalar data', function () {
    $validator = new Validator();

    $validator->setRules([
        'name' => Str::required(),
    ]);
    $validator->setData([
        'name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($validator->passes())->toBeTrue();
    expect($validator->validated())->toBe([
        'name' => 'John',
    ]);
});

it('runs successfully validation using corresponding fails method', function () {
    $validator = new Validator();

    $validator->setRules([
        'name' => Str::required(),
    ]);
    $validator->setData([
        'name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($validator->fails())->toBeFalsy();
    expect($validator->validated())->toBe([
        'name' => 'John',
    ]);
});

it('returns validated data directly', function () {
    $validator = new Validator();

    $validator->setRules([
        'name' => Str::required(),
    ]);
    $validator->setData([
        'name' => 'John',
    ]);

    expect($validator->validate())->toBe([
        'name' => 'John',
    ]);
});

it('runs failed validation with scalar data', function () {
    $validator = new Validator();

    $validator->setRules([
        'name' => Str::required(),
    ]);
    $validator->setData([
        'last_name' => 'Doe',
    ]);

    expect($validator->passes())->toBeFalse();

    expect($validator->failing())->toBe([
        'name' => [Required::class],
    ]);

    expect($validator->invalid())->toBe([
        'name' => null,
    ]);

    $validator->validated();
})->throws(InvalidData::class);

it('runs successfully validation with dictionary data', function () {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Dictionary::required()->min(2)->define([
            'name' => Str::required()->min(3),
            'last_name' => Str::required()->min(3),
        ]),
    ]);

    $validator->setData([
        'customer' => [
            'name' => 'John',
            'last_name' => 'Doe',
            'address' => 'Spring street',
        ],
    ]);

    expect($validator->passes())->toBeTrue();
    expect($validator->validated())->toBe([
        'customer' => [
            'name' => 'John',
            'last_name' => 'Doe',
        ],
    ]);
});

it('throws error on an invalid dictionary definition', function (array $definition) {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Dictionary::required()->min(2)->define($definition),
    ]);
})
->throws(InvalidDictionaryDefinition::class)
->with([
    'list' => [['value']],
    'dictionary without rules' => [['key' => 'value']],
    'dictionary without scalar type' => [['key' => ArrList::required()]],
]);

it('runs data failed validation with dictionary data', function () {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Dictionary::required()->min(2)->define([
            'name' => Str::required()->min(3),
            'email' => Str::required()->min(12),
        ]),
    ]);

    $validator->setData([
        'customer' => [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => ['john.doe@mail.com'],
        ],
    ]);

    expect($validator->passes())->toBeFalsy();

    expect($validator->failing())->toBe([
        'customer' => [IsDictionary::class],
        'customer.email' => [IsString::class],
    ]);

    expect($validator->invalid())->toBe([
        'customer' => [
            'email' => ['john.doe@mail.com'],
        ],
    ]);
});

it('runs successfully validation with collection data', function () {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Collection::required()->min(2)->define([
            'name' => Str::required()->min(3)->max(20),
        ]),
    ]);

    $validator->setData([
        'customer' => [
            [
                'name' => 'John',
                'last_name' => 'Doe',
            ],
            [
                'name' => 'Bob',
                'last_name' => 'Ross',
            ],
        ],
    ]);

    expect($validator->passes())->toBeTrue();
    expect($validator->validated())->toBe([
        'customer' => [
            [
                'name' => 'John',
            ],
            [
                'name' => 'Bob',
            ],
        ],
    ]);
});

it('throws error on an invalid collection definition', function (array $definition) {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Collection::required()->min(2)->define($definition),
    ]);
})
->throws(InvalidCollectionDefinition::class)
->with([
    'list' => [['value']],
    'dictionary without rules' => [['key' => 'value']],
]);

it('runs successfully validation with list data', function () {
    $validator = new Validator();

    $validator->setRules([
        'weekdays' => ArrList::required()->define(Str::required()),
    ]);

    $validator->setData([
        'weekdays' => ['monday', 'sunday'],
        'months' => ['january'],
    ]);

    expect($validator->passes())->toBeTrue();
    expect($validator->validated())->toBe([
        'weekdays' => ['monday', 'sunday'],
    ]);
});

it('does not stop validating all types when one of them fails', function () {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Dictionary::required()->min(2)->define([
            'name' => Str::required()->min(3),
            'email' => Str::required()->min(12),
        ]),
        'date' => Str::required()->min(10),
        'merchant' => Str::required()->min(3),
    ]);

    $validator->setData([
        'customer' => [
            'name' => 'John Doe',
            'email' => 'john.doe@mail.com',
        ],
        'merchant' => 'My merchant',
    ]);

    expect($validator->passes())->toBeFalsy();

    expect($validator->failing())->toBe([
        'date' => [Required::class],
    ]);

    expect($validator->invalid())->toBe([
        'date' => null,
    ]);

    $validator->validated();
})->throws(InvalidData::class);

it('stops validating all types when one of them fails', function () {
    $validator = new Validator();
    $validator->stopOnFailure();

    $validator->setRules([
        'customer' => Dictionary::required()->min(2)->define([
            'name' => Str::required()->min(3),
            'email' => Str::required()->min(12),
        ]),
        'date' => Str::required()->min(10),
        'merchant' => Str::required()->min(3),
    ]);

    $validator->setData([
        'customer' => [
            'name' => 'John Doe',
            'email' => 'john.doe@mail.com',
        ],
        'merchant' => 'My merchant',
    ]);

    expect($validator->passes())->toBeFalse();

    expect($validator->failing())->toBe([
        'date' => [Required::class],
    ]);

    expect($validator->invalid())->toBe([
        'date' => null,
    ]);

    $validator->validated();
})->throws(InvalidData::class);

it('runs successfully validation with array data', function () {
    $validator = new Validator();

    $validator->setRules([
        'customer' => Arr::required()->min(2)->define([
            'full_name' => Str::required()->min(8),
            'links' => ArrList::required()->min(2)->max(20)->define(Str::required()),
        ]),
    ]);

    $validator->setData([
        'customer' => [
            'full_name' => 'John Doe',
            'links' => [
                'https://twitter.com/@Jhon.Doe',
                'https://facebook.com/@Jhon.Doe',
            ],
        ],
    ]);

    expect($validator->passes())->toBeTrue();
    expect($validator->validated())->toBe([
        'customer' => [
            'full_name' => 'John Doe',
            'links' => [
                'https://twitter.com/@Jhon.Doe',
                'https://facebook.com/@Jhon.Doe',
            ],
        ],
    ]);
});

it('runs successfully validation with optional data', function (array $data) {
    $validator = new Validator();

    $validator->setRules([
        'full_name' => Str::required()->min(8),
        'address' => Str::optional()->min(10),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBeTrue();
})->with([
    'present value' => [['full_name' => 'John Doe', 'address' => '350 Fifth Avenue']],
    'missing value' => [['full_name' => 'John Doe']],
]);

it('runs failed validation with optional data', function (array $data) {
    $validator = new Validator();

    $validator->setRules([
        'full_name' => Str::required()->min(8),
        'address' => Str::optional()->min(10),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBeFalse();
})->with([
    'null value' => [['full_name' => 'John Doe', 'address' => null]],
    'empty value' => [['full_name' => 'John Doe', 'address' => '']],
    'empty value with space' => [['full_name' => 'John Doe', 'address' => ' ']],
]);

it('runs successfully validation with nullable data', function (array $data) {
    $validator = new Validator();

    $validator->setRules([
        'full_name' => Str::required()->min(8),
        'address' => Str::nullable()->min(10),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBeTrue();
    expect($validator->validated())->toBe($data);
})->with([
    'present value' => [['full_name' => 'John Doe', 'address' => '350 Fifth Avenue']],
    'null value' => [['full_name' => 'John Doe', 'address' => null]],
]);

it('runs failed validation with nullable data', function (array $data) {
    $validator = new Validator();

    $validator->setRules([
        'full_name' => Str::required()->min(8),
        'address' => Str::nullable()->min(10),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBeFalse();
})->with([
    'missing value' => [['full_name' => 'John Doe']],
    'empty value' => [['full_name' => 'John Doe', 'address' => '']],
    'empty value with space' => [['full_name' => 'John Doe', 'address' => ' ']],
]);

it('runs successful validation with date related to date', function (array $data, bool $expected) {
    $validator = new Validator();

    $validator->setRules([
        'departure_date' => Date::required()->equalToday(),
        'return_date' => Date::required()->equalTo('departure_date'),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBe($expected);
})->with([
    'date is equal to related date' => [
        [
            'departure_date' => Dates::now()->toDateString(),
            'return_date' => Dates::now()->toDateString(),
        ],
        true,
    ],
    'date is not equal to related date' => [
        [
            'departure_date' => Dates::now()->toDateString(),
            'return_date' => Dates::now()->addDay()->toDateString(),
        ],
        false,
    ],
]);

it('runs successful validation with date related to date in a dictionary', function (array $data, bool $expected) {
    $validator = new Validator();

    $validator->setRules([
        'dates' => Dictionary::required()->size(2)->define([
            'departure_date' => Date::required()->equalToday(),
            'return_date' => Date::required()->equalTo('departure_date'),
        ]),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBe($expected);
})->with([
    'date is equal to related date' => [
        [
            'dates' => [
                'departure_date' => Dates::now()->toDateString(),
                'return_date' => Dates::now()->toDateString(),
            ],
        ],
        true,
    ],
    'date is not equal to related date' => [
        [
            'dates' => [
                'departure_date' => Dates::now()->toDateString(),
                'return_date' => Dates::now()->addDay()->toDateString(),
            ],
        ],
        false,
    ],
]);

it('runs successful validation with date related to date in a collection', function (array $data, bool $expected) {
    $validator = new Validator();

    $validator->setRules([
        'flies' => Collection::required()->max(2)->define([
            'departure_date' => Date::required()->equalToday(),
            'return_date' => Date::required()->equalTo('departure_date'),
        ]),
    ]);

    $validator->setData($data);

    expect($validator->passes())->toBe($expected);
})->with([
    'date is equal to related date' => [
        [
            'flies' => [
                [
                    'departure_date' => Dates::now()->toDateString(),
                    'return_date' => Dates::now()->toDateString(),
                ],
            ],
        ],
        true,
    ],
    'date is not equal to related date' => [
        [
            'flies' => [
                [
                    'departure_date' => Dates::now()->toDateString(),
                    'return_date' => Dates::now()->addDay()->toDateString(),
                ],
            ],
        ],
        false,
    ],
]);

<?php

declare(strict_types=1);

use Phenix\Validation\Rules\{
    Rule as BaseRule,
    Required,
    Nullable,
    Optional,
    Size,
    Min,
    Max,
    Between,
    Confirmed,
    In,
    NotIn,
    Exists,
    Unique,
    Mimes,
    RegEx,
    StartsWith,
    EndsWith,
    DoesNotStartWith,
    DoesNotEndWith,
    IsString,
    IsArray,
    IsBool,
    IsFile,
    IsUrl,
    IsEmail,
    Uuid,
    Ulid
};
use Phenix\Validation\Rules\Numbers\{IsInteger, IsNumeric, IsFloat};
use Phenix\Validation\Rules\Dates\{IsDate, After, Format};
use Phenix\Database\QueryBuilder;

it('returns base fallback message', function (): void {
    $rule = new class extends BaseRule { public function passes(): bool { return false; } };
    $rule->setField('field')->setData([]);

    expect($rule->message())->toBe('The field is invalid.');
});

it('returns null for nullable when failing (missing field)', function (): void {
    $rule = new Nullable();
    $rule->setField('foo')->setData([]);

    expect($rule->passes())->toBeFalse();
    expect($rule->message())->toBeNull();
});

it('returns null for optional when failing (empty string)', function (): void {
    $rule = new Optional();
    $rule->setField('foo')->setData(['foo' => '']);

    expect($rule->passes())->toBeFalse();
    expect($rule->message())->toBeNull();
});

it('builds size/min/max/between messages', function () {
    $size = (new Size(3))->setField('name')->setData(['name' => 'John']);

    expect($size->passes())->toBeFalse();
    expect($size->message())->toContain('must be 3 characters');

    $min = (new Min(5))->setField('name')->setData(['name' => 'John']);

    expect($min->passes())->toBeFalse();
    expect($min->message())->toContain('at least 5 characters');


    $max = (new Max(2))->setField('items')->setData(['items' => ['a','b','c']]);

    expect($max->passes())->toBeFalse();
    expect($max->message())->toContain('more than 2 items');

    $between = (new Between(2,4))->setField('items')->setData(['items' => ['a','b','c','d','e']]);

    expect($between->passes())->toBeFalse();
    expect($between->message())->toContain('between 2 and 4 items');
});

it('string and type messages', function () {
    $string = (new IsString())->setField('name')->setData(['name' => 123]);

    expect($string->passes())->toBeFalse();
    expect($string->message())->toContain('must be a string');

    $array = (new IsArray())->setField('arr')->setData(['arr' => 'not-array']);
    expect($array->passes())->toBeFalse();
    expect($array->message())->toContain('must be an array');

    $bool = (new IsBool())->setField('bool')->setData(['bool' => 'not-bool']);
    expect($bool->passes())->toBeFalse();
    expect($bool->message())->toContain('must be true or false');
});

it('other scalar type messages', function () {
    $int = (new IsInteger())->setField('age')->setData(['age' => '12']);

    expect($int->passes())->toBeFalse();
    expect($int->message())->toContain('must be an integer');

    $num = (new IsNumeric())->setField('code')->setData(['code' => 'abc']);

    expect($num->passes())->toBeFalse();
    expect($num->message())->toContain('must be a number');

    $float = (new IsFloat())->setField('ratio')->setData(['ratio' => 10]);

    expect($float->passes())->toBeFalse();
    expect($float->message())->toContain('must be a float');
});

it('format/uuid/url/email messages', function () {
    $file = (new IsFile())->setField('upload')->setData(['upload' => 'not-file']);

    expect($file->passes())->toBeFalse();
    expect($file->message())->toContain('must be a file');

    $url = (new IsUrl())->setField('site')->setData(['site' => 'notaurl']);

    expect($url->passes())->toBeFalse();
    expect($url->message())->toContain('valid URL');

    $email = (new IsEmail())->setField('email')->setData(['email' => 'invalid']);

    expect($email->passes())->toBeFalse();
    expect($email->message())->toContain('valid email');

    $uuid = (new Uuid())->setField('id')->setData(['id' => 'not-uuid']);

    expect($uuid->passes())->toBeFalse();
    expect($uuid->message())->toContain('valid UUID');

    $ulid = (new Ulid())->setField('id')->setData(['id' => 'not-ulid']);

    expect($ulid->passes())->toBeFalse();
    expect($ulid->message())->toContain('valid ULID');
});

it('in / not in messages', function () {
    $in = (new In(['a','b']))->setField('val')->setData(['val' => 'c']);

    expect($in->passes())->toBeFalse();
    expect($in->message())->toContain('Allowed');

    $notIn = (new NotIn(['a','b']))->setField('val')->setData(['val' => 'a']);

    expect($notIn->passes())->toBeFalse();
    expect($notIn->message())->toContain('Disallowed');
});

it('regex and start/end messages', function () {
    $regex = (new RegEx('/^[0-9]+$/'))->setField('code')->setData(['code' => 'abc']);

    expect($regex->passes())->toBeFalse();
    expect($regex->message())->toContain('format is invalid');

    $starts = (new StartsWith('pre'))->setField('text')->setData(['text' => 'post']);

    expect($starts->passes())->toBeFalse();
    expect($starts->message())->toContain('must start with');

    $ends = (new EndsWith('suf'))->setField('text')->setData(['text' => 'prefix']);

    expect($ends->passes())->toBeFalse();
    expect($ends->message())->toContain('must end with');

    $dns = (new DoesNotStartWith('pre'))->setField('text')->setData(['text' => 'prefix']);

    expect($dns->passes())->toBeFalse();
    expect($dns->message())->toContain('must not start with');

    $dne = (new DoesNotEndWith('suf'))->setField('text')->setData(['text' => 'endsuf']);

    expect($dne->passes())->toBeFalse();
    expect($dne->message())->toContain('must not end with');
});

it('confirmed rule message', function () {
    $confirmed = (new Confirmed('password_confirmation'))->setField('password')->setData([
        'password' => 'secret1',
        'password_confirmation' => 'secret2',
    ]);

    expect($confirmed->passes())->toBeFalse();
    expect($confirmed->message())->toContain('does not match');
});

// Skipping exists/unique due to heavy QueryBuilder dependencies; would require DB mocking layer.

it('date related messages', function () {
    $isDate = (new IsDate())->setField('start')->setData(['start' => 'not-date']);

    expect($isDate->passes())->toBeFalse();
    expect($isDate->message())->toContain('not a valid date');

    $format = (new Format('Y-m-d'))->setField('start')->setData(['start' => '2020/01/01']);

    expect($format->passes())->toBeFalse();
    expect($format->message())->toContain('does not match the format');
});
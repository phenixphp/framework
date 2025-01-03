<?php

declare(strict_types=1);

use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Phenix\Database\Constants\Connection;
use Phenix\Database\QueryBuilder;
use Phenix\Validation\Rules\IsEmail;
use Phenix\Validation\Types\Email;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

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

it('runs validation for emails with custom validators', function () {
    $rules = Email::required()->validations(new DNSCheckValidation())->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('email');
        $rule->setData(['email' => 'Abc@ietf.org']);

        expect($rule->passes())->toBeTruthy();
    }
});

it('runs validation to check if email exists in database', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['exists' => 1]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $rules = Email::required()->exists('users')->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('email');
        $rule->setData(['email' => 'Abc@ietf.org']);

        expect($rule->passes())->toBeTruthy();
    }
});

it('runs validation to check if email exists in database with custom column', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['exists' => 1]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $rules = Email::required()->exists('users', 'user_email')->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('email');
        $rule->setData(['email' => 'Abc@ietf.org']);

        expect($rule->passes())->toBeTruthy();
    }
});

it('runs validation to check if email is unique in database', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['COUNT(*)' => 1]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $rules = Email::required()->unique('users')->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('email');
        $rule->setData(['email' => 'Abc@ietf.org']);

        expect($rule->passes())->toBeTruthy();
    }
});

it('runs validation to check if email is unique in database except one other email', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['COUNT(*)' => 1]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $rules = Email::required()->unique(table: 'users', query: function (QueryBuilder $queryBuilder) {
        $queryBuilder->whereDistinct('email', 'john.doe@mail.com');
    })->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('email');
        $rule->setData(['email' => 'Abc@ietf.org']);

        expect($rule->passes())->toBeTruthy();
    }
});

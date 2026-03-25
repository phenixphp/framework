<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Facades\DB;
use Phenix\Validation\Rules\Unique;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

it('fails validation when value already exists (count > 0)', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([[ 'COUNT(*)' => 1 ]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $unique = new Unique(DB::from('users'), 'email');
    $unique->setData(['email' => 'user@example.com']);
    $unique->setField('email');

    assertFalse($unique->passes());
    assertSame('The email has already been taken.', (string) $unique->message());
});

it('passes validation when value does not exist (count == 0)', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([[ 'COUNT(*)' => 0 ]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $unique = new Unique(DB::from('users'), 'email');
    $unique->setData(['email' => 'user@example.com']);
    $unique->setField('email');

    assertTrue($unique->passes());
});

it('passes validation when value does not exist using custom column', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([[ 'COUNT(*)' => 0 ]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $unique = new Unique(DB::from('users'), 'user_email');
    $unique->setData(['email' => 'user@example.com']);
    $unique->setField('email');

    assertTrue($unique->passes());
});

<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Facades\DB;
use Phenix\Validation\Rules\Exists;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

it('fails when email does not exists in database', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['exists' => 0]])),
        );

    $this->app->swap(Connection::default(), $connection);

    $exists = new Exists(DB::from('users'), 'email');
    $exists->setData(['email' => 'Abc@ietf.org']);
    $exists->setField('email');

    expect($exists->passes())->toBeFalse();
    expect($exists->message())->toBe('The selected email is invalid.');
});

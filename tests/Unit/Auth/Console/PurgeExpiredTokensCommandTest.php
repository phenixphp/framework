<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

it('purges expired tokens and reports count', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $countResult = new Result([['count' => 3]]);
    $deleteResult = new Result([['Query OK']]);

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement($countResult),
            new Statement($deleteResult),
        );

    $this->app->swap(Connection::default(), $connection);

    /** @var CommandTester $command */
    $command = $this->phenix('tokens:purge');

    $command->assertCommandIsSuccessful();

    $display = $command->getDisplay();

    expect($display)->toContain('3 expired token(s) purged successfully.');
});

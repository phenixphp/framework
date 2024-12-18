<?php

declare(strict_types=1);

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Phenix\Runtime\Log;

it('can log messages successfully', function () {
    $handler = new TestHandler();

    $logger = new Logger('phenix');
    $logger->pushHandler($handler);

    $log = new Log($logger);

    $log->info('This is an info message');
    $log->warning('This is a warning message');
    $log->error('This is an error message');
    $log->debug('This is a debug message');
    $log->notice('This is a notice message');
    $log->critical('This is a critical message');
    $log->alert('This is an alert message');
    $log->emergency('This is an emergency message');

    /** @var array<int, LogRecord> $records */
    $records = $handler->getRecords();

    expect($records)->toHaveCount(8);
});

<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\LogRecord;
use Phenix\Runtime\Log;
use Monolog\Handler\TestHandler;
use Phenix\Exceptions\RuntimeError;

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

it('reports an exception in logs', function () {
    $handler = new TestHandler();

    $logger = new Logger('phenix');
    $logger->pushHandler($handler);

    $log = new Log($logger);

    $this->app->swap(Log::class, $log);

    try {
        throw new RuntimeError('This is an exception');
    } catch (Exception $e) {
        report($e);
    }

    /** @var array<int, LogRecord> $records */
    $records = $handler->getRecords();

    expect($records)->toHaveCount(1);
    expect($records[0]->message)->toBe('This is an exception');
});

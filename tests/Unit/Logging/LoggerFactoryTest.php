<?php

declare(strict_types=1);

use Amp\Log\ConsoleFormatter;
use Monolog\Formatter\LineFormatter;
use Phenix\Exceptions\RuntimeError;
use Phenix\Logging\LoggerFactory;

it('makes all supported logger channels', function (string $channel, string $formatter) {
    $logger = LoggerFactory::make($channel);

    /** @var \Amp\Log\StreamHandler $handler */
    $handler = $logger->getHandlers()[0];

    expect($handler->getFormatter())->toBeInstanceOf($formatter);
})->with([
    ['file', LineFormatter::class],
    ['stream', ConsoleFormatter::class],
]);

it('throws error on unsupported channel', function () {
    expect(function () {
        LoggerFactory::make('unsupported');
    })->toThrow(RuntimeError::class);
});

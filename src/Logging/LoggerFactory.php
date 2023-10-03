<?php

declare(strict_types=1);

namespace Phenix\Logging;

use Amp\ByteStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Phenix\Contracts\Makeable;
use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\File;

class LoggerFactory implements Makeable
{
    public static function make(string $key): Logger
    {
        $logHandler = match ($key) {
            'file' => self::fileHandler(),
            'stream' => self::streamHandler(),
            default => throw new RuntimeError("Unsupported logging channel: {$key}")
        };

        $logger = new Logger('phenix');
        $logger->pushHandler($logHandler);

        return $logger;
    }

    private static function streamHandler(): StreamHandler
    {
        $logHandler = new StreamHandler(ByteStream\getStdout());
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new ConsoleFormatter());

        return $logHandler;
    }

    private static function fileHandler(): StreamHandler
    {
        $file = File::openFile(base_path('storage/framework/logs/phenix.log'));

        $logHandler = new StreamHandler($file);
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new LineFormatter());

        return $logHandler;
    }
}

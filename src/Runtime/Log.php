<?php

namespace Phenix\Runtime;

use Monolog\Logger;
use Stringable;

class Log
{
    public function __construct(
        protected Logger $logger
    ) {
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->logger->debug((string) $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->info((string) $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->logger->notice((string) $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->warning((string) $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->logger->error((string) $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical((string) $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->logger->alert((string) $message, $context);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->logger->emergency((string) $message, $context);
    }
}

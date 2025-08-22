<?php

declare(strict_types=1);

namespace Phenix\Crypto\Exceptions;

use RuntimeException;

class MissingKeyException extends RuntimeException
{
    public function __construct(string $message = 'Missing app key.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}

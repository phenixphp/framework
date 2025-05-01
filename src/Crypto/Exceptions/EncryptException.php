<?php

declare(strict_types=1);

namespace Phenix\Crypto\Exceptions;

use RuntimeException;

class EncryptException extends RuntimeException
{
    public function __construct(string $message = 'Encryption failed.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}

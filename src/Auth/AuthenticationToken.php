<?php

declare(strict_types=1);

namespace Phenix\Auth;

use Phenix\Util\Date;
use Stringable;

class AuthenticationToken implements Stringable
{
    public function __construct(
        protected string $token,
        protected Date $expiresAt,
    ) {
    }

    public function toString(): string
    {
        return $this->token;
    }

    public function expiresAt(): Date
    {
        return $this->expiresAt;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use DateTimeImmutable;
use Phenix\Validation\Rules\IsString;
use Throwable;

use function is_string;

class IsDate extends IsString
{
    public function passes(): bool
    {
        $value = $this->getValue();

        try {
            $dateTime = new DateTimeImmutable($value);

            return is_string($value) && $dateTime->getLastErrors() === false;
        } catch (Throwable $e) {
            report($e);

            return false;
        }

    }
}

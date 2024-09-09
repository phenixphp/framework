<?php

declare(strict_types=1);

namespace Phenix\Validation\Validations;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Validation\EmailValidation as ValidationContract;

class EmailValidation implements ValidationContract
{
    public function isValid(string $email, EmailLexer $emailLexer): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false;
    }

    public function getError(): ?InvalidEmail
    {
        return null;
    }

    public function getWarnings(): array
    {
        return [];
    }
}

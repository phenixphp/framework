<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Phenix\Validation\Validations\EmailValidation as FilterEmailValidation;

class IsEmail extends IsString
{
    protected array $emailValidations;

    public function __construct()
    {
        $this->emailValidations = [new FilterEmailValidation(), new RFCValidation()];
    }

    public function passes(): bool
    {
        $emailValidator = new EmailValidator();

        return parent::passes() && $emailValidator->isValid(
            $this->getValue(),
            new MultipleValidationWithAnd($this->emailValidations)
        );
    }

    public function pusValidation(EmailValidation $emailValidation): self
    {
        $this->emailValidations[] = $emailValidation;

        return $this;
    }
}

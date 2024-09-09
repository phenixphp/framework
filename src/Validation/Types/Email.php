<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Egulias\EmailValidator\Validation\EmailValidation;
use Phenix\Validation\Rules\IsEmail;
use Phenix\Validation\Rules\TypeRule;

/**
 * @property IsEmail $type
 */
class Email extends Str
{
    protected function defineType(): TypeRule
    {
        return IsEmail::new();
    }

    public function validations(EmailValidation ...$emailValidations): self
    {

        foreach ($emailValidations as $emailValidation) {
            $this->type->pusValidation($emailValidation);
        }

        return $this;
    }
}

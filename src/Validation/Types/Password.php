<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Closure;
use Phenix\Validation\Rules\Confirmed;

class Password extends Str
{
    public function secure(Closure|bool $enforce = true): self
    {
        if ($enforce instanceof Closure) {
            $enforce = $enforce();
        }

        if ($enforce) {
            $this->min(12);
            $this->max(48);

            $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{12,48}$/';
            $this->regex($pattern);

            return $this;
        }

        $this->min(8);
        $this->max(12);

        return $this;
    }

    public function confirmed(string $confirmationField = 'password_confirmation'): self
    {
        $this->rules['confirmed'] = new Confirmed($confirmationField);

        return $this;
    }
}

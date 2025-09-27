<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Confirmed extends Rule
{
    public function __construct(
        protected string $confirmationField
    ) {
    }

    public function passes(): bool
    {
        $original = $this->getValue();
        $confirmation = $this->data->get($this->confirmationField);

        return $original !== null
            && $confirmation !== null
            && $original === $confirmation;
    }
}

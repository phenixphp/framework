<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class EqualTo extends RelatedTo
{
    public function passes(): bool
    {
        $date = Date::parse($this->getValue());
        $relatedDate = Date::parse($this->getRelatedValue());

        return $date->equalTo($relatedDate);
    }

    public function message(): string|null
    {
        return trans('validation.date.equal_to', [
            'field' => $this->field,
            'other' => $this->relatedField,
        ]);
    }
}

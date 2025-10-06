<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class BeforeTo extends RelatedTo
{
    public function passes(): bool
    {
        $date = Date::parse($this->getValue());
        $relatedDate = Date::parse($this->getRelatedValue());

        return  Date::parse($date)->lessThan($relatedDate);
    }

    public function message(): string|null
    {
        return trans('validation.date.before_to', [
            'field' => $this->field,
            'other' => $this->relatedField,
        ]);
    }
}

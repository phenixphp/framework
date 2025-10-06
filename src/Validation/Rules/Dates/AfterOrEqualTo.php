<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class AfterOrEqualTo extends RelatedTo
{
    public function passes(): bool
    {
        $date = Date::parse($this->getValue());
        $relatedDate = Date::parse($this->getRelatedValue());

        return  Date::parse($date)->greaterThanOrEqualTo($relatedDate);
    }

    public function message(): string|null
    {
        return trans('validation.date.after_or_equal_to', [
            'field' => $this->field,
            'other' => $this->relatedField,
        ]);
    }
}

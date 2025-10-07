<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class AfterTo extends RelatedTo
{
    public function passes(): bool
    {
        $date = Date::parse($this->getValue());
        $relatedDate = Date::parse($this->getRelatedValue());

        return  Date::parse($date)->greaterThan($relatedDate);
    }

    public function message(): string|null
    {
        return trans('validation.date.after_to', [
            'field' => $this->getFieldForHumans(),
            'other' => $this->relatedField,
        ]);
    }
}

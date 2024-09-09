<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Validation\Rules\Rule;

use function array_pop;

abstract class RelatedTo extends Rule
{
    public function __construct(
        protected string $relatedField
    ) {
    }

    protected function getRelatedValue(): string
    {
        return $this->data->get($this->prepareRelatedField());
    }

    protected function prepareRelatedField(): string
    {
        $path = explode('.', $this->field);

        array_pop($path);

        $path[] = $this->relatedField;

        return implode('.', $path);
    }
}

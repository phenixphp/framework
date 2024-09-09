<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Contracts\Rule;
use Phenix\Validation\Contracts\Type as TypeContract;
use Phenix\Validation\Rules\Nullable;
use Phenix\Validation\Rules\Optional;
use Phenix\Validation\Rules\Required;
use Phenix\Validation\Rules\Requirement;
use Phenix\Validation\Rules\TypeRule;

abstract class Type implements TypeContract
{
    protected TypeRule $type;
    protected array $rules;

    public function __construct(
        protected Requirement $requirement,
    ) {
        $this->type = $this->defineType();
        $this->rules = [];
    }

    abstract protected function defineType(): TypeRule;

    public static function required(): static
    {
        return new static(Required::new());
    }

    public static function optional(): static
    {
        return new static(Optional::new());
    }

    public static function nullable(): static
    {
        return new static(Nullable::new());
    }

    public function isRequired(): bool
    {
        return $this->requirement instanceof Required;
    }

    /**
     * @return array{ type: Rule[], definition: mixed[] }
     */
    public function toArray(): array
    {
        return [
            'type' => [
                $this->requirement,
                $this->type,
                ...array_values($this->rules),
            ],
            'definition' => [],
        ];
    }
}

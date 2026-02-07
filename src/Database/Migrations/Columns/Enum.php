<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Enum extends Column
{
    public function __construct(
        protected string $name,
        protected array $values
    ) {
        parent::__construct($name);
        $this->options['values'] = $values;
    }

    public function getType(): string
    {
        if ($this->isSQLite()) {
            return 'string';
        }

        return 'enum';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function values(array $values): static
    {
        $this->values = $values;

        $this->options['values'] = $values;

        return $this;
    }

    public function getOptions(): array
    {
        $options = parent::getOptions();

        if ($this->isSQLite() && ! empty($this->values)) {
            $quotedValues = array_map(fn ($v) => "'{$v}'", $this->values);

            $valuesString = implode(', ', $quotedValues);

            $options['comment'] = ($options['comment'] ?? '') .
                " CHECK({$this->name} IN ({$valuesString}))";
        }

        return $options;
    }
}

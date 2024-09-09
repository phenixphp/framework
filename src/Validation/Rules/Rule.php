<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Adbar\Dot;
use Phenix\Validation\Contracts\Rule as RuleContract;

use function is_array;

abstract class Rule implements RuleContract
{
    protected string $field;
    protected Dot $data;

    public function __construct(array|null $data = null)
    {
        $this->setData($data ?? []);
    }

    public static function new(mixed ...$args): static
    {
        return new static(...$args);
    }

    abstract public function passes(): bool;

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function setData(Dot|array $data): self
    {
        $this->data = is_array($data) ? new Dot($data) : $data;

        return $this;
    }

    protected function getValue(): array|string|int|float|bool|null
    {
        return $this->data->get($this->field);
    }

    protected function getValueType(): string
    {
        return gettype($this->data->get($this->field) ?? null);
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Validation;

use Adbar\Dot;
use ArrayIterator;
use function is_null;
use function in_array;
use function array_filter;
use function array_unique;
use Phenix\Contracts\Arrayable;
use Phenix\Validation\Contracts\Rule;

use Phenix\Validation\Contracts\Type;
use Phenix\Validation\Types\Collection;
use Phenix\Validation\Types\Dictionary;
use Phenix\Validation\Rules\Requirement;
use Phenix\Validation\Exceptions\InvalidData;

class Validator
{
    protected Dot $data;
    protected ArrayIterator $rules;
    protected bool $stopOnFail = false;
    protected array $failing = [];
    protected array $validated = [];
    protected array $errors = [];

    public function setRules(array $rules = []): self
    {
        $this->rules = new ArrayIterator($rules);

        return $this;
    }

    public function setData(array $data = []): self
    {
        $this->data = new Dot($data);

        return $this;
    }

    public function validate(): array
    {
        $this->runValidation();

        return $this->validated();
    }

    public function passes(): bool
    {
        $this->runValidation();

        return empty($this->failing);
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function stopOnFailure(): self
    {
        $this->stopOnFail = true;

        return $this;
    }

    public function validated(): array
    {
        if (! empty($this->failing)) {
            $failures = array_keys($this->failing);

            throw new InvalidData('Invalid data detected: ' . array_shift($failures));
        }

        return $this->getDataFromKeys(array_unique($this->validated));
    }

    public function failing(): array
    {
        return $this->failing;
    }

    public function invalid(): array
    {
        return $this->getDataFromKeys(array_keys($this->failing));
    }

    protected function runValidation(): void
    {
        $this->reset();

        $this->checkRules($this->rules);
    }

    protected function shouldStop(): bool
    {
        return $this->stopOnFail && ! empty($this->failing);
    }

    protected function reset(): void
    {
        $this->failing = [];
        $this->validated = [];
        $this->rules->rewind();
    }

    protected function checkRules(ArrayIterator $rules, string|int|null $parent = null): void
    {
        while ($rules->valid() && ! $this->shouldStop()) {
            $field = $rules->key();

            /** @var Type $type */
            $type = $rules->current();

            $ruleSet = $type->toArray();

            $passes = true;

            foreach ($ruleSet['type'] as $rule) {
                $passes = $this->checkRule($field, $rule, $parent);
                $skip = $rule instanceof Requirement ? $rule->skip() : false;

                if (! $passes || $skip) {
                    break;
                }
            }

            if (isset($ruleSet['definition'])) {
                $this->checkDefinition($field, $type, $ruleSet['definition'], $parent);
            }

            $rules->next();
        }
    }

    protected function checkDefinition(
        string $field,
        Type $type,
        Arrayable|array $rules,
        string|int|null $parent = null
    ): void {
        $rules = new ArrayIterator($rules);

        if ($type instanceof Collection) {
            $this->checkCollection($rules, $this->implodeKeys([$parent, $field]));
        } else {
            $this->checkRules($rules, $this->implodeKeys([$parent, $field]));
        }
    }

    protected function checkRule(string $field, Rule $rule, string|int|null $parent = null): bool
    {
        $field = $this->implodeKeys([$parent, $field]);

        $rule->setField($field)
            ->setData($this->data);

        $passes = $rule->passes();

        if (! $passes) {
            $this->failing[$field][] = $rule::class;
        }

        $this->validated[] = $field;

        return $passes;
    }

    protected function checkCollection(ArrayIterator $rules, string|int|null $parent = null): void
    {
        $count = is_null($parent) ? count($this->data) : count($this->data[$parent]);

        for ($i = 0; $i < $count; $i++) {
            $this->checkRules($rules, $this->implodeKeys([$parent, $i]));

            $rules->rewind();
        }
    }

    protected function implodeKeys(array $keys): string
    {
        return implode('.', array_filter($keys, fn ($key) => ! is_null($key)));
    }

    protected function getDataFromKeys(array $keys)
    {
        $validated = new Dot();

        foreach ($keys as $key) {
            /** @var Type $type */
            $type = $this->rules[$key] ?? null;

            if ($type && in_array($type::class, [Collection::class, Dictionary::class])) {
                $validated->set($key, []);
            } elseif ($this->data->has($key)) {
                $validated->set($key, $this->data->get($key));
            } elseif (! $this->data->has($key) && $type->isRequired()) {
                $validated->set($key, null);
            }
        }

        return $validated->all();
    }
}

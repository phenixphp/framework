<?php

declare(strict_types=1);

namespace Phenix\Data;

use Closure;
use Phenix\Contracts\Arrayable;
use Ramsey\Collection\Collection as GenericCollection;
use Ramsey\Collection\CollectionInterface;
use Ramsey\Collection\Exception\CollectionMismatchException;
use Ramsey\Collection\Sort;

use function array_filter;
use function array_key_first;
use function array_map;
use function array_merge;
use function array_udiff;
use function array_uintersect;
use function is_int;
use function is_object;
use function spl_object_id;
use function sprintf;
use function usort;

/**
 * @template T
 * @extends GenericCollection<T>
 */
class Collection extends GenericCollection implements Arrayable
{
    public static function fromArray(array $data): self
    {
        $collection = new self(self::getDataType($data));
        $collection->data = $data;

        return $collection;
    }

    public function first(): mixed
    {
        $firstIndex = array_key_first($this->data);

        if ($firstIndex === null) {
            return null;
        }

        return $this->data[$firstIndex];
    }

    /**
     * @param callable(T): bool $callback
     *
     * @return self<T>
     */
    public function filter(callable $callback): self
    {
        $collection = clone $this;
        $collection->data = array_merge([], array_filter($collection->data, $callback));

        return $collection;
    }

    /**
     * @param callable(T): TCallbackReturn $callback
     *
     * @return self<TCallbackReturn>
     *
     * @template TCallbackReturn
     */
    public function map(callable $callback): self
    {
        return new self('mixed', array_map($callback, $this->data));
    }

    /**
     * @param string|null $propertyOrMethod
     * @param mixed $value
     *
     * @return self<T>
     */
    public function where(string|null $propertyOrMethod, mixed $value): self
    {
        return $this->filter(
            fn (mixed $item): bool => $this->extractValue($item, $propertyOrMethod) === $value,
        );
    }

    /**
     * @param string|null $propertyOrMethod
     * @param Sort $order
     *
     * @return self<T>
     */
    public function sort(string|null $propertyOrMethod = null, Sort $order = Sort::Ascending): self
    {
        $collection = clone $this;

        usort(
            $collection->data,
            function (mixed $a, mixed $b) use ($propertyOrMethod, $order): int {
                $aValue = $this->extractValue($a, $propertyOrMethod);
                $bValue = $this->extractValue($b, $propertyOrMethod);

                return ($aValue <=> $bValue) * ($order === Sort::Descending ? -1 : 1);
            },
        );

        return $collection;
    }

    /**
     * @param CollectionInterface<T> $other
     *
     * @return self<T>
     */
    public function diff(CollectionInterface $other): self
    {
        $this->compareCollectionTypes($other);

        $diffAtoB = array_udiff($this->data, $other->toArray(), $this->getComparator());
        $diffBtoA = array_udiff($other->toArray(), $this->data, $this->getComparator());

        $collection = clone $this;
        $collection->data = array_merge($diffAtoB, $diffBtoA);

        return $collection;
    }

    /**
     * @param CollectionInterface<T> $other
     *
     * @return self<T>
     */
    public function intersect(CollectionInterface $other): self
    {
        $this->compareCollectionTypes($other);

        $collection = clone $this;
        $collection->data = array_uintersect($this->data, $other->toArray(), $this->getComparator());

        return $collection;
    }

    /**
     * @param CollectionInterface<T> ...$collections
     *
     * @return self<T>
     */
    public function merge(CollectionInterface ...$collections): self
    {
        $mergedCollection = clone $this;

        foreach ($collections as $index => $collection) {
            if (! $collection instanceof static) {
                throw new CollectionMismatchException(
                    sprintf('Collection with index %d must be of type %s', $index, static::class),
                );
            }

            if ($this->getUniformType($collection) !== $this->getUniformType($this)) {
                throw new CollectionMismatchException(
                    sprintf(
                        'Collection items in collection with index %d must be of type %s',
                        $index,
                        $this->getType(),
                    ),
                );
            }

            foreach ($collection as $key => $value) {
                if (is_int($key)) {
                    $mergedCollection[] = $value;
                } else {
                    $mergedCollection[$key] = $value;
                }
            }
        }

        return $mergedCollection;
    }

    /**
     * @param CollectionInterface<T> $other
     *
     * @throws CollectionMismatchException
     */
    private function compareCollectionTypes(CollectionInterface $other): void
    {
        if (! $other instanceof static) {
            throw new CollectionMismatchException('Collection must be of type ' . static::class);
        }

        if ($this->getUniformType($other) !== $this->getUniformType($this)) {
            throw new CollectionMismatchException('Collection items must be of type ' . $this->getType());
        }
    }

    private function getComparator(): Closure
    {
        return function (mixed $a, mixed $b): int {
            if (is_object($a) && is_object($b)) {
                $a = spl_object_id($a);
                $b = spl_object_id($b);
            }

            return $a === $b ? 0 : ($a < $b ? 1 : -1);
        };
    }

    /**
     * @param CollectionInterface<mixed> $collection
     */
    private function getUniformType(CollectionInterface $collection): string
    {
        return match ($collection->getType()) {
            'integer' => 'int',
            'boolean' => 'bool',
            'double' => 'float',
            default => $collection->getType(),
        };
    }

    /**
     * @param array<mixed> $data
     *
     * @return string
     */
    private static function getDataType(array $data): string
    {
        if (empty($data)) {
            return 'mixed';
        }

        $firstType = gettype(reset($data));

        if (count($data) === 1) {
            return $firstType;
        }

        foreach ($data as $item) {
            if (gettype($item) !== $firstType) {
                return 'mixed';
            }
        }

        return $firstType;
    }
}

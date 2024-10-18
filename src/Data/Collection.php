<?php

declare(strict_types=1);

namespace Phenix\Data;

use Phenix\Contracts\Arrayable;
use Ramsey\Collection\Collection as GenericCollection;
use SplFixedArray;

use function array_key_first;

class Collection extends GenericCollection implements Arrayable
{
    public static function fromArray(array $data): self
    {
        $data = SplFixedArray::fromArray($data);
        $collection = new self('array');

        foreach ($data as $value) {
            $collection->add($value);
        }

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
}

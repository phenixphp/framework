<?php

declare(strict_types=1);

namespace Phenix\Data;

use Phenix\Contracts\Arrayable;
use Ramsey\Collection\Collection as GenericCollection;
use SplFixedArray;

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
}

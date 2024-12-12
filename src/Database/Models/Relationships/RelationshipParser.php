<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

use Closure;
use Phenix\Contracts\Arrayable;

class RelationshipParser implements Arrayable
{
    protected array $mappedRelationships;

    public function __construct(
        protected array $relationships
    ) {
        $this->mappedRelationships = [];
    }

    public function parse(): self
    {
        $this->mappedRelationships = $this->parseRelations();

        return $this;
    }

    public function toArray(): array
    {
        return $this->mappedRelationships;
    }

    protected function parseRelations(): array
    {
        $relations = [];

        foreach ($this->relationships as $key => $value) {
            $columns = ['*'];

            if ($value instanceof Closure) {
                $relationKey = $key;
                $columns = $value;
            } else {
                $relationKey = $value;
            }

            $current = &$relations;

            $keys = explode('.', $relationKey);

            foreach ($keys as $relationName) {
                if (!isset($current[$relationName])) {
                    if (str_contains($relationName, ':')) {
                        [$relationName, $columns] = explode(':', $relationName);

                        $columns = explode(',', $columns);
                    }

                    $current[$relationName] = [
                        'columns' => $columns,
                        'relationships' => [],
                    ];
                }

                $current = &$current[$relationName]['relationships'];
            }
        }

        return $relations;
    }
}

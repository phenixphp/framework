<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Relationships;

abstract class BelongsToRelationship extends Relationship
{
    protected bool $chaperone = false;

    public function withChaperone(): self
    {
        $this->chaperone = true;

        return $this;
    }

    public function assignChaperone(): bool
    {
        return $this->chaperone;
    }
}

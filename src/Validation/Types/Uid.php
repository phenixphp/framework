<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\Ulid;
use Phenix\Validation\Rules\Uuid;

class Uid extends Str
{
    public function uuid(): self
    {
        $this->rules['uid'] = Uuid::new();

        return $this;
    }

    public function ulid(): self
    {
        $this->rules['uid'] = Ulid::new();

        return $this;
    }
}

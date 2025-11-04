<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

trait HasSign
{
    public function unsigned(): static
    {
        $this->options['signed'] = false;

        return $this;
    }

    public function signed(): static
    {
        $this->options['signed'] = true;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Database\Contracts;

interface ModelAttribute
{
    public function getColumnName(): string|null;
}

<?php

declare(strict_types=1);

namespace Phenix\Contracts\Database;

interface ModelAttribute
{
    public function getColumnName(): string|null;
}

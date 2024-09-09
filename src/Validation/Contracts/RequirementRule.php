<?php

declare(strict_types=1);

namespace Phenix\Validation\Contracts;

interface RequirementRule
{
    public function skip(): bool;
}

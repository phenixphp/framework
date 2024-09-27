<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Amp\Http\Server\FormParser\BufferedFile;

class IsFile extends TypeRule
{
    public function passes(): bool
    {
        $value = $this->getValue();

        return $value instanceof BufferedFile;
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\IsFile;
use Phenix\Validation\Rules\Max;
use Phenix\Validation\Rules\Mimes;
use Phenix\Validation\Rules\Min;
use Phenix\Validation\Rules\Size;
use Phenix\Validation\Rules\TypeRule;

class File extends Type
{
    protected function defineType(): TypeRule
    {
        return IsFile::new();
    }

    /**
     * Minimum size limit in kilobytes.
     *
     * @param float|int $limit
     * @return File
     */
    public function min(float|int $limit): static
    {
        $this->rules['min'] = Min::new($limit);

        return $this;
    }

    /**
     * Maximum size limit in kilobytes.
     *
     * @param float|int $limit
     * @return File
     */
    public function max(float|int $limit): static
    {
        $this->rules['max'] = Max::new($limit);

        return $this;
    }

    /**
     * Size limit in kilobytes.
     *
     * @param float|int $limit
     * @return File
     */
    public function size(float|int $size): static
    {
        $this->rules['size'] = Size::new($size);

        return $this;
    }

    public function mimes(array $mimes): static
    {
        $this->rules['mimes'] = Mimes::new($mimes);

        return $this;
    }
}

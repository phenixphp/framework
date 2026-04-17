<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use Phenix\Http\FormRequest;

class LimitedBodyRequest extends FormRequest
{
    protected function rules(): array
    {
        return [];
    }

    protected function bodySizeLimit(): int
    {
        return 16;
    }
}

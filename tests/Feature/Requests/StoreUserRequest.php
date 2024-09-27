<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use Phenix\Http\FormRequest;
use Phenix\Validation\Types\Email;
use Phenix\Validation\Types\Str;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => Str::required()->max(10),
            'email' => Email::required(),
        ];
    }
}

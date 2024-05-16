<?php

declare(strict_types=1);

namespace Phenix\Constants;

enum ContentType: string
{
    case JSON = 'application/json';
    case FORM_DATA = 'multipart/form-data';
    case FORM_URLENCODED = 'application/x-www-form-urlencoded';

    public static function fromValue(string|null $value): self
    {
        $value ??= '';

        if (str_starts_with($value, self::FORM_DATA->value)) {
            return self::FORM_DATA;
        } elseif (str_starts_with($value, self::FORM_URLENCODED->value)) {
            return self::FORM_URLENCODED;
        } else {
            return self::JSON;
        }
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use InvalidArgumentException;
use Phenix\Crypto\Constants\Bin2Base64Mode;
use Phenix\Util\Utility;

final class Bin2Base64 extends Utility
{
    public static function encode(string $data, Bin2Base64Mode $mode = Bin2Base64Mode::BASE_64): string
    {
        $encoded = match ($mode) {
            Bin2Base64Mode::BASE_64 => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_ORIGINAL),
            Bin2Base64Mode::BASE_64_NO_PADDING => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_ORIGINAL_NO_PADDING),
            Bin2Base64Mode::BASE_64_URL => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_URLSAFE),
            Bin2Base64Mode::BASE_64_URL_NO_PADDING => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
        };

        return "{$mode->value}:{$encoded}";
    }

    public static function decode(string $data): string
    {
        if (str_contains($data, ':')) {
            [$mode, $encoded] = explode(':', $data, 2);

            return match ($mode) {
                Bin2Base64Mode::BASE_64->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL),
                Bin2Base64Mode::BASE_64_NO_PADDING->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL_NO_PADDING),
                Bin2Base64Mode::BASE_64_URL->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_URLSAFE),
                Bin2Base64Mode::BASE_64_URL_NO_PADDING->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                default => throw new InvalidArgumentException("Invalid base64 mode: {$mode}"),
            };
        }

        return sodium_base642bin($data, SODIUM_BASE64_VARIANT_ORIGINAL);
    }
}

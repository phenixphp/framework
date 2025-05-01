<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Constants\BinBase64Mode;
use Phenix\Util\Utility;

final class Bin2Base64 extends Utility
{
    public static function encode(string $data, BinBase64Mode $mode = BinBase64Mode::BASE_64): string
    {
        $encoded = match ($mode) {
            BinBase64Mode::BASE_64 => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_ORIGINAL),
            BinBase64Mode::BASE_64_NO_PADDING => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_ORIGINAL_NO_PADDING),
            BinBase64Mode::BASE_64_URL => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_URLSAFE),
            BinBase64Mode::BASE_64_URL_NO_PADDING => sodium_bin2base64($data, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
        };

        return "{$mode->value}:{$encoded}";
    }

    public static function decode(string $data): string
    {
        [$mode, $encoded] = explode(':', $data, 2);

        return match ($mode) {
            BinBase64Mode::BASE_64->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL),
            BinBase64Mode::BASE_64_NO_PADDING->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_ORIGINAL_NO_PADDING),
            BinBase64Mode::BASE_64_URL->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_URLSAFE),
            BinBase64Mode::BASE_64_URL_NO_PADDING->value => sodium_base642bin($encoded, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
        };
    }
}

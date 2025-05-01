<?php

declare(strict_types=1);

namespace Phenix\Crypto\Constants;

enum Bin2Base64Mode: string
{
    case BASE_64 = 'base64';

    case BASE_64_NO_PADDING = 'base64_np';

    case BASE_64_URL = 'base64url';

    case BASE_64_URL_NO_PADDING = 'base64url_np';
}

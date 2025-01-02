<?php

declare(strict_types=1);

namespace Phenix\Session\Constants;

use Amp\Http\Cookie\CookieAttributes;

enum SameSite: string
{
    case STRICT = CookieAttributes::SAMESITE_STRICT;
    case LAX = CookieAttributes::SAMESITE_LAX;
    case NONE = CookieAttributes::SAMESITE_NONE;
}

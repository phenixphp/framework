<?php

declare(strict_types=1);

namespace Phenix\Http\Constants;

enum Protocol: string
{
    case HTTP = 'http';

    case HTTPS = 'https';
}

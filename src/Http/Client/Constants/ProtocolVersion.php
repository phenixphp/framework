<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Constants;

enum ProtocolVersion: string
{
    case V1_0 = '1.0';

    case V1_1 = '1.1';

    case V2 = '2';
}
<?php

declare(strict_types=1);

namespace Phenix\Session\Constants;

enum Driver: string
{
    case LOCAL = 'local';
    case REDIS = 'redis';
}

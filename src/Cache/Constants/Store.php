<?php

declare(strict_types=1);

namespace Phenix\Cache\Constants;

enum Store: string
{
    case LOCAL = 'local';

    case FILE = 'file';

    case REDIS = 'redis';
}

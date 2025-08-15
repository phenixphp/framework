<?php

declare(strict_types=1);

namespace Phenix\Queue\Constants;

enum QueueDriver: string
{
    case PARALLEL = 'parallel';

    case REDIS = 'redis';

    case DATABASE = 'database';
}

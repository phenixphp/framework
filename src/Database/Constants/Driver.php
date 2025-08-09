<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum Driver: string
{
    case MYSQL = 'mysql';

    case POSTGRESQL = 'postgresql';

    case SQLITE = 'sqlite';

    case REDIS = 'redis';
}

<?php

declare(strict_types=1);

namespace Phenix\Constants;

enum ServerMode: string
{
    case SINGLE = 'single';

    case CLUSTER = 'cluster';
}

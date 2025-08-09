<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum Lock
{
    case FOR_UPDATE;
    case FOR_SHARE;
    case FOR_UPDATE_SKIP_LOCKED;
    case FOR_UPDATE_NOWAIT;
}

<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum Lock
{
    case FOR_UPDATE;

    case FOR_SHARE;

    case FOR_UPDATE_SKIP_LOCKED;

    case FOR_UPDATE_NOWAIT;

    case FOR_NO_KEY_UPDATE;

    case FOR_KEY_SHARE;

    case FOR_SHARE_NOWAIT;

    case FOR_SHARE_SKIP_LOCKED;

    case FOR_NO_KEY_UPDATE_NOWAIT;

    case FOR_NO_KEY_UPDATE_SKIP_LOCKED;
}

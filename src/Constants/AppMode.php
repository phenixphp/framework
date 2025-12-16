<?php

declare(strict_types=1);

namespace Phenix\Constants;

enum AppMode: string
{
    case DIRECT = 'direct';

    case PROXIED = 'proxied';
}

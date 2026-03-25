<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum ClauseType
{
    case BASIC;

    case NESTED;

    case RAW;

    case IN;

    case BETWEEN;
}

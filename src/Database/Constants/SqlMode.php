<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum SqlMode
{
    case Raw;

    case Prepared;
}

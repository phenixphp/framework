<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum SqlMark: string
{
    case Placeholder = '{?}';
}

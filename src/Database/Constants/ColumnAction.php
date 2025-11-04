<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum ColumnAction: string
{
    case SET_NULL = 'SET_NULL';

    case NO_ACTION = 'NO_ACTION';

    case CASCADE = 'CASCADE';

    case RESTRICT = 'RESTRICT';
}

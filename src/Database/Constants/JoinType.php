<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum JoinType: string
{
    case INNER = 'INNER JOIN';
    case LEFT = 'LEFT JOIN';
    case LEFT_OUTER = 'LEFT OUTER JOIN';
    case RIGHT = 'RIGHT JOIN';
    case RIGHT_OUTER = 'RIGHT OUTER JOIN';
    case CROSS = 'CROSS JOIN';
}

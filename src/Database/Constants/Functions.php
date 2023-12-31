<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum Functions
{
    case AVG;
    case SUM;
    case MIN;
    case MAX;
    case COUNT;
    case DATE;
    case MONTH;
    case YEAR;
}

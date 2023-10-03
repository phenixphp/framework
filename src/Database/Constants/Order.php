<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum Order: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}

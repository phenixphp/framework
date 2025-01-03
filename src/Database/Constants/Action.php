<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

enum Action
{
    case SELECT;
    case EXISTS;
    case INSERT;
    case UPDATE;
    case DELETE;
}

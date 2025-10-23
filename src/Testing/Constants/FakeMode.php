<?php

declare(strict_types=1);

namespace Phenix\Testing\Constants;

enum FakeMode
{
    case NONE;

    case ALL;

    case SCOPED;

    case EXCEPT;
}

<?php

declare(strict_types=1);

namespace Phenix\Constants;

enum RequestMode
{
    case BUFFERED;
    case STREAMED;
}

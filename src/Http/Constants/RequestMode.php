<?php

declare(strict_types=1);

namespace Phenix\Http\Constants;

enum RequestMode
{
    case BUFFERED;
    case STREAMED;
}

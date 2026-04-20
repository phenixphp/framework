<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use Phenix\Http\Constants\RequestMode;

class LimitedStreamedRequest extends LimitedBodyRequest
{
    protected function mode(): RequestMode
    {
        return RequestMode::STREAMED;
    }
}

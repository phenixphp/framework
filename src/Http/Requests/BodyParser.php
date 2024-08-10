<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\Request;
use Phenix\Contracts\Http\Requests\BodyParser as RequestsBodyContract;

abstract class BodyParser implements RequestsBodyContract
{
    abstract protected function parse(Request $request): self;
}

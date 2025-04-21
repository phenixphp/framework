<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\Request;
use Phenix\Http\Contracts\BodyParser as BodyParserContract;

abstract class BodyParser implements BodyParserContract
{
    abstract protected function parse(Request $request): self;
}

<?php

declare(strict_types=1);

namespace Phenix\Http;

use Phenix\Concerns\HasRequest;

abstract class Controller
{
    use HasRequest;
}

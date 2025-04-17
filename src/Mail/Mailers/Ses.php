<?php

declare(strict_types=1);

namespace Phenix\Mail\Mailers;

use Phenix\Facades\Config;
use Phenix\Mail\Mailer;

class Ses extends Mailer
{
    protected function serviceConfig(): array
    {
        return Config::get('services.ses');
    }
}

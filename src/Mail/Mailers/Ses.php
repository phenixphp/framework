<?php

declare(strict_types=1);

namespace Phenix\Mail\Mailers;

use Phenix\Facades\Config;
use Phenix\Mail\Mailer;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Ses extends Mailer
{
    protected function resolveTransport(): TransportInterface
    {
        $sesConfig = Config::get('services.ses');

        return match ($this->config['transport']) {
            'log' => new LogTransport(),
            'ses' => new SesSmtpTransport($sesConfig['key'], $sesConfig['secret'], $sesConfig['region']),
            default => new SesSmtpTransport($sesConfig['key'], $sesConfig['secret'], $sesConfig['region']),
        };
    }
}

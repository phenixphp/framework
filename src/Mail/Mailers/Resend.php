<?php

declare(strict_types=1);

namespace Phenix\Mail\Mailers;

use Phenix\Facades\Config;
use Phenix\Mail\Mailer;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Resend extends Mailer
{
    protected function resolveTransport(): TransportInterface
    {
        $resendConfig = Config::get('services.resend');

        return match ($this->config['transport']) {
            'log' => new LogTransport(),
            'resend' => new ResendApiTransport($resendConfig['key']),
            default => new ResendApiTransport($resendConfig['key']),
        };
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Mail\Mailers;

use Phenix\Mail\Mailer;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Smtp extends Mailer
{
    protected function resolveTransport(): TransportInterface
    {
        $factory = new EsmtpTransportFactory();

        return match ($this->config['transport']) {
            'log' => new LogTransport(),
            'smtp' => $factory->create($this->dsn()),
            default => $factory->create($this->dsn()),
        };
    }

    public function dsn(): Dsn
    {
        $scheme = ! empty($this->config['encryption']) && $this->config['encryption'] === 'tls'
            ? (($this->config['port'] == 465) ? 'smtps' : 'smtp')
            : '';

        return new Dsn(
            $scheme,
            $this->config['host'],
            $this->config['username'] ?? null,
            $this->config['password'] ?? null,
            $this->config['port'] ?? null,
            $this->config
        );
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Mail;

use InvalidArgumentException;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TransportFactory
{
    public static function make(array $mailerConfig, array $serviceConfig = []): TransportInterface
    {
        return match ($mailerConfig['transport']) {
            'smtp' => self::createSmtpTransport($mailerConfig),
            'ses' => new SesSmtpTransport($serviceConfig['key'], $serviceConfig['secret'], $serviceConfig['region']),
            'log' => new LogTransport(),
            'resend' => new ResendApiTransport($serviceConfig['key']),
            default => throw new InvalidArgumentException("Unsupported transport: {$mailerConfig['transport']}"),
        };
    }

    private static function createSmtpTransport(array $config): TransportInterface
    {
        $factory = new EsmtpTransportFactory();

        $scheme = 'smtp';

        if (!empty($config['encryption']) && $config['encryption'] === 'tls') {
            $scheme = ($config['port'] === 465) ? 'smtps' : 'smtp';
        }

        $dsn = new Dsn(
            $scheme,
            $config['host'],
            $config['username'] ?? null,
            $config['password'] ?? null,
            $config['port'] ?? null,
            $config
        );

        return $factory->create($dsn);
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Mail;

use InvalidArgumentException;
use Phenix\Mail\Constants\MailerType;
use Phenix\Mail\Transports\LogTransport;
use SensitiveParameter;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;

class TransportFactory
{
    public static function make(#[SensitiveParameter] array $mailerConfig,  #[SensitiveParameter]array $serviceConfig = []): TransportInterface
    {
        return match ($mailerConfig['transport']) {
            MailerType::SMTP->value => self::createSmtpTransport($mailerConfig),
            MailerType::AMAZON_SES->value => new SesSmtpTransport($serviceConfig['key'], $serviceConfig['secret'], $serviceConfig['region']),
            MailerType::RESEND->value => new ResendApiTransport($serviceConfig['key']),
            'log' => new LogTransport(),
            default => throw new InvalidArgumentException("Unsupported transport: {$mailerConfig['transport']}"),
        };
    }

    private static function createSmtpTransport(#[SensitiveParameter] array $config): TransportInterface
    {
        $factory = new EsmtpTransportFactory();

        $scheme = 'smtp';

        if (! empty($config['encryption']) && $config['encryption'] === 'tls') {
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

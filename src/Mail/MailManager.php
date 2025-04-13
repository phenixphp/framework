<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Closure;
use Phenix\Facades\Config as Configuration;
use Phenix\Mail\Constants\MailerDriver;
use Phenix\Mail\Contracts\Mailer as MailerContract;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class MailManager
{
    protected array $mailers = [];

    protected MailerDriver|null $loggableMailerDriver;

    protected Config $config;

    public function __construct(
        Config|null $config = new Config()
    ) {
        $this->config = $config;
        $this->loggableMailerDriver = null;
    }

    public function mailer(string|null $mailer = null): MailerContract
    {
        $mailer ??= $this->config->default();

        $mailer = MailerDriver::tryFrom($mailer) ?? MailerDriver::SMTP;

        return $this->mailers[$mailer->value] ??= $this->resolve($mailer);
    }

    public function using(string $mailer): MailerContract
    {
        return $this->mailer($mailer);
    }

    public function to(array|string $to): MailerContract
    {
        return $this->mailer()->to($to);
    }

    public function send(Mailable $mailable, array $data = [], Closure|null $callback = null): void
    {
        $this->mailer()->send($mailable, $data, $callback);
    }

    public function log(string|null $mailer = null): void
    {
        $mailer ??= $this->config->default();

        $this->config->setLogTransport($mailer);

        $this->loggableMailerDriver = MailerDriver::tryFrom($mailer) ?? MailerDriver::SMTP;
    }

    protected function resolve(MailerDriver $mailer): MailerContract
    {
        return match ($mailer) {
            MailerDriver::SMTP => $this->createSmtpDriver(),
            MailerDriver::AMAZON_SES => $this->createSesDriver(),
            MailerDriver::RESEND => $this->createResendDriver(),
            default => $this->createSmtpDriver(),
        };
    }

    protected function createSmtpDriver(): MailerContract
    {
        $factory = new EsmtpTransportFactory();

        $transport = match ($this->config->transport(MailerDriver::SMTP->value)) {
            'log' => new LogTransport(),
            'smtp' => $factory->create($this->config->dsn()),
            default => $factory->create($this->config->dsn()),
        };

        return new Mailer($transport, $this->config);
    }

    protected function createSesDriver(): MailerContract
    {
        $sesConfig = Configuration::get('services.ses');

        $transport = match ($this->config->transport(MailerDriver::AMAZON_SES->value)) {
            'log' => new LogTransport(),
            'ses' => new SesSmtpTransport($sesConfig['key'], $sesConfig['secret'], $sesConfig['region']),
            default => new SesSmtpTransport($sesConfig['key'], $sesConfig['secret'], $sesConfig['region']),
        };

        return new Mailer($transport, $this->config);
    }

    protected function createResendDriver(): MailerContract
    {
        $resendConfig = Configuration::get('services.resend');

        $transport = match ($this->config->transport(MailerDriver::RESEND->value)) {
            'log' => new LogTransport(),
            'resend' => new ResendApiTransport($resendConfig['key']),
            default => new ResendApiTransport($resendConfig['key']),
        };

        return new Mailer($transport, $this->config);
    }
}

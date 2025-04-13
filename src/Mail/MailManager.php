<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Closure;
use Phenix\Mail\Constants\MailerDriver;
use Phenix\Mail\Contracts\Mailer as MailerContract;
use Phenix\Mail\Mailers\Resend;
use Phenix\Mail\Mailers\Ses;
use Phenix\Mail\Mailers\Smtp;

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
        $mailer = $this->resolveDriver($mailer);

        return $this->mailers[$mailer->value] ??= $this->resolveMailer($mailer);
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

    public function log(MailerDriver|null $mailer = null): void
    {
        if (! $mailer) {
            $mailer = MailerDriver::from($this->config->default());
        }

        $this->loggableMailerDriver = $mailer;

        $this->config->setLogTransport($mailer->value);
    }

    protected function resolveMailer(MailerDriver $mailer): MailerContract
    {
        return match ($mailer) {
            MailerDriver::SMTP => $this->createSmtpDriver(),
            MailerDriver::AMAZON_SES => $this->createSesDriver(),
            MailerDriver::RESEND => $this->createResendDriver(),
            default => $this->createSmtpDriver(),
        };
    }

    protected function resolveDriver(string|null $mailer = null): MailerDriver
    {
        if ($this->loggableMailerDriver) {
            return $this->loggableMailerDriver;
        }

        $mailer ??= $this->config->default();

        return MailerDriver::tryFrom($mailer) ?? MailerDriver::SMTP;
    }

    protected function createSmtpDriver(): MailerContract
    {
        return new Smtp($this->config->from(), $this->config->get(MailerDriver::SMTP));
    }

    protected function createSesDriver(): MailerContract
    {
        return new Ses($this->config->from(), $this->config->get(MailerDriver::AMAZON_SES));
    }

    protected function createResendDriver(): MailerContract
    {
        return new Resend($this->config->from(), $this->config->get(MailerDriver::RESEND));
    }
}

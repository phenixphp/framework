<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Closure;
use Phenix\Mail\Constants\MailerType;
use Phenix\Mail\Contracts\Mailer as MailerContract;
use Phenix\Mail\Mailers\Resend;
use Phenix\Mail\Mailers\Ses;
use Phenix\Mail\Mailers\Smtp;

class MailManager
{
    protected array $mailers = [];

    protected MailerType|null $loggableMailerType;

    protected Config $config;

    public function __construct(
        Config|null $config = new Config()
    ) {
        $this->config = $config;
        $this->loggableMailerType = null;
    }

    public function mailer(string|null $mailer = null): MailerContract
    {
        $mailer = $this->resolveMailerType($mailer);

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

    public function log(MailerType|null $mailer = null): void
    {
        if (! $mailer) {
            $mailer = MailerType::from($this->config->default());
        }

        $this->loggableMailerType = $mailer;

        $this->config->setLogTransport($mailer->value);
    }

    protected function resolveMailer(MailerType $mailer): MailerContract
    {
        return match ($mailer) {
            MailerType::SMTP => $this->createSmtpDriver(),
            MailerType::AMAZON_SES => $this->createSesDriver(),
            MailerType::RESEND => $this->createResendDriver(),
            default => $this->createSmtpDriver(),
        };
    }

    protected function resolveMailerType(string|null $mailer = null): MailerType
    {
        if ($this->loggableMailerType) {
            return $this->loggableMailerType;
        }

        $mailer ??= $this->config->default();

        return MailerType::tryFrom($mailer) ?? MailerType::SMTP;
    }

    protected function createSmtpDriver(): MailerContract
    {
        return new Smtp($this->config->from(), $this->config->get(MailerType::SMTP));
    }

    protected function createSesDriver(): MailerContract
    {
        return new Ses($this->config->from(), $this->config->get(MailerType::AMAZON_SES));
    }

    protected function createResendDriver(): MailerContract
    {
        return new Resend($this->config->from(), $this->config->get(MailerType::RESEND));
    }
}

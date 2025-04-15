<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Mail;
use Phenix\Mail\Constants\MailerType;
use Phenix\Mail\Mailable;
use Phenix\Mail\Mailers\Resend;
use Phenix\Mail\Mailers\Ses;
use Phenix\Mail\Mailers\Smtp;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function Pest\Faker\faker;

it('build smtp transport', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(EsmtpTransport::class);

    $mailer = Mail::using(MailerType::SMTP->value)->to($email);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(EsmtpTransport::class);
});

it('build ses transport', function (): void {
    Config::set('services.ses', [
        'key' => 'key',
        'secret' => 'secret',
        'region' => 'region',
    ]);

    $email = faker()->freeEmail();

    $mailer = Mail::using(MailerType::AMAZON_SES->value)->to($email);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(SesSmtpTransport::class);
});

it('build resend transport', function (): void {
    Config::set('services.resend', [
        'key' => 'key',
    ]);

    $email = faker()->freeEmail();

    $mailer = Mail::using(MailerType::RESEND->value)->to($email);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(ResendApiTransport::class);
});

it('build log transport for smtp mailer', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    Mail::log();

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

    expect($mailer)->toBeInstanceOf(Smtp::class);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(LogTransport::class);

    Mail::log(MailerType::SMTP);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

    expect($mailer)->toBeInstanceOf(Smtp::class);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(LogTransport::class);
});

it('build log transport for ses mailer', function (): void {
    Config::set('mail.mailers.ses', [
        'transport' => 'ses',
    ]);

    Mail::log(MailerType::AMAZON_SES);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

    expect($mailer)->toBeInstanceOf(Ses::class);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(LogTransport::class);
});

it('build log transport for resend mailer', function (): void {
    Config::set('mail.mailers.resend', [
        'transport' => 'resend',
    ]);

    Mail::log(MailerType::RESEND);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

    expect($mailer)->toBeInstanceOf(Resend::class);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(LogTransport::class);
});

it('send email successfully using smtp mailer', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    Mail::log();

    $email = faker()->freeEmail();

    $mailable = new class () extends Mailable {
        public function build(): self
        {
            return $this->view('emails.welcome')
                ->subject('Welcome to the team');
        }
    };

    Mail::to($email)->send($mailable);

    Mail::expect()->toBeSent($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches): bool {
        return $matches['success'] === true;
    });
});

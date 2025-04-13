<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Mail;
use Phenix\Mail\Constants\MailerDriver;
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

    $mailer = Mail::using(MailerDriver::SMTP->value)->to($email);

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

    $mailer = Mail::using(MailerDriver::AMAZON_SES->value)->to($email);

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

    $mailer = Mail::using(MailerDriver::RESEND->value)->to($email);

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

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(LogTransport::class);

    Mail::log(MailerDriver::SMTP);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

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

    Mail::log(MailerDriver::AMAZON_SES);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

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

    Mail::log(MailerDriver::RESEND);

    $email = faker()->freeEmail();

    $mailer = Mail::to($email);

    $reflection = new ReflectionClass($mailer);

    $property = $reflection->getProperty('transport');
    $property->setAccessible(true);

    $transport = $property->getValue($mailer);

    expect($transport)->toBeInstanceOf(LogTransport::class);
});

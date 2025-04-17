<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Mail;
use Phenix\Mail\Mailable;
use Phenix\Mail\TransportFactory;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

use function Pest\Faker\faker;

it('build smtp transport', function (): void {
    $transport = TransportFactory::make([
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    expect($transport)->toBeInstanceOf(EsmtpTransport::class);
});

it('build ses transport', function (): void {
    $transport = TransportFactory::make([
        'transport' => 'ses',
    ], [
        'key' => 'key',
        'secret' => 'secret',
        'region' => 'region',
    ]);

    expect($transport)->toBeInstanceOf(SesSmtpTransport::class);
});

it('build resend transport', function (): void {
    $transport = TransportFactory::make(
        [
            'transport' => 'resend',
        ],
        [
            'key' => 'key',
        ]
    );

    expect($transport)->toBeInstanceOf(ResendApiTransport::class);
});

it('build log transport', function (): void {
    $transport = TransportFactory::make([
        'transport' => 'log',
    ]);

    expect($transport)->toBeInstanceOf(LogTransport::class);
});

it('throw exception for unsupported transport', function (): void {
    TransportFactory::make([
        'transport' => 'unsupported',
    ]);
})->throws(InvalidArgumentException::class, 'Unsupported transport: unsupported');

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

    Mail::expect()->toBeSentTimes($mailable, 1);
    Mail::expect()->toNotBeSent($mailable, function (array $matches): bool {
        return $matches['success'] === false;
    });
});

it('send email successfully using smtps', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 465,
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

    Mail::expect()->toBeSentTimes($mailable, 1);
    Mail::expect()->toNotBeSent($mailable, function (array $matches): bool {
        return $matches['success'] === false;
    });
});

it('send email successfully using smtp mailer with sender defined in mailable', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    Mail::log();

    $mailable = new class () extends Mailable {
        public function build(): self
        {
            return $this->to(faker()->freeEmail())
                ->view('emails.welcome')
                ->subject('Welcome to the team');
        }
    };

    Mail::send($mailable);

    Mail::expect()->toBeSent($mailable);
});

it('merge sender defined from facade and mailer', function (): void {
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
            return $this->to(faker()->freeEmail())
                ->view('emails.welcome')
                ->subject('Welcome to the team');
        }
    };

    Mail::to($email)->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches): bool {
        return count($matches['to']) === 2;
    });
});

it('send email successfully using cc', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    Mail::log();

    $to = faker()->freeEmail();
    $cc = faker()->freeEmail();

    $mailable = new class () extends Mailable {
        public function build(): self
        {
            return $this->view('emails.welcome')
                ->subject('Welcome with CC');
        }
    };

    Mail::to($to)
        ->cc($cc)
        ->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches) use ($cc): bool {
        return $matches['cc'][0]->getAddress() === $cc;
    });
});

it('send email successfully using bcc', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    Mail::log();

    $to = faker()->freeEmail();
    $bcc = faker()->freeEmail();

    $mailable = new class () extends Mailable {
        public function build(): self
        {
            return $this->view('emails.welcome')
                ->subject('Welcome with BCC');
        }
    };

    Mail::to($to)
        ->bcc($bcc)
        ->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches) use ($bcc): bool {
        return $matches['bcc'][0]->getAddress() === $bcc;
    });
});

it('send email successfully using reply to', function (): void {
    Config::set('mail.mailers.smtp', [
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    Mail::log();

    $to = faker()->freeEmail();

    $mailable = new class () extends Mailable {
        public function build(): self
        {
            return $this->replyTo(faker()->freeEmail())
                ->view('emails.welcome')
                ->subject('Welcome with BCC');
        }
    };

    Mail::to($to)
        ->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches): bool {
        return isset($matches['replyTo'][0]);
    });
});

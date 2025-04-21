<?php

declare(strict_types=1);

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Facades\Config;
use Phenix\Facades\Mail;
use Phenix\Mail\Constants\MailerType;
use Phenix\Mail\Mailable;
use Phenix\Mail\Mailers\Resend;
use Phenix\Mail\Mailers\Ses;
use Phenix\Mail\Mailers\Smtp;
use Phenix\Mail\Tasks\SendEmail;
use Phenix\Mail\TransportFactory;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesSmtpTransport;
use Symfony\Component\Mailer\Bridge\Resend\Transport\ResendApiTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

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

it('build smtp transport without encryption', function (): void {
    $transport = TransportFactory::make([
        'transport' => 'smtp',
        'host' => 'smtp.server.com',
        'port' => 2525,
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
    expect((string) $transport)->toBe('LogTransport');
});

it('throw exception for unsupported transport', function (): void {
    TransportFactory::make([
        'transport' => 'unsupported',
    ]);
})->throws(InvalidArgumentException::class, 'Unsupported transport: unsupported');

it('build smtp mailer', function (): void {
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

    expect($mailer)->toBeInstanceOf(Smtp::class);
});

it('build ses mailer', function (): void {
    $email = faker()->freeEmail();

    $mailer = Mail::using(MailerType::AMAZON_SES)->to($email);

    expect($mailer)->toBeInstanceOf(Ses::class);
});

it('build resend mailer', function (): void {
    $email = faker()->freeEmail();

    $mailer = Mail::using(MailerType::RESEND)->to($email);

    expect($mailer)->toBeInstanceOf(Resend::class);
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
                ->subject('It will be sent');
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
                ->subject('It will be sent with smtps');
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
                ->subject('It will be sent with sender');
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
                ->subject('It merges sender');
        }
    };

    Mail::to($email)->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches): bool {
        $email = $matches['email'] ?? null;

        if (! $email) {
            return false;
        }

        $headers = $email->getHeaders();
        $fromHeader = $headers->get('From');

        return $fromHeader !== null;
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
        $email = $matches['email'] ?? null;

        if (! $email) {
            return false;
        }

        $headers = $email->getHeaders();

        $ccHeader = $headers->get('Cc');

        return $ccHeader->getAddresses()[0]->getAddress() === $cc;
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
        $email = $matches['email'] ?? null;

        if (! $email) {
            return false;
        }

        $headers = $email->getHeaders();

        $bccHeader = $headers->get('Bcc');

        return $bccHeader->getAddresses()[0]->getAddress() === $bcc;
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
        $email = $matches['email'] ?? null;

        if (! $email) {
            return false;
        }

        $headers = $email->getHeaders();
        $replyTo = $headers->get('Reply-To');

        return $replyTo !== null;
    });
});

it('send email with multi attachments', function (): void {
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
            return $this->view('emails.welcome')
                ->subject('Welcome with Attachment')
                ->attachments([
                    dirname(__DIR__, 2) . '/fixtures/files/lorem.txt',
                    [
                        'path' => dirname(__DIR__, 2) . '/fixtures/files/lorem.txt',
                        'name' => 'archivo.txt',
                        'mime' => 'text/plain',
                    ],
                ]);
        }
    };

    Mail::to($to)->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches): bool {
        $email = $matches['email'] ?? null;
        if (! $email) {
            return false;
        }

        $attachments = $email->getAttachments();

        if (count($attachments) !== 2) {
            return false;
        }

        foreach ($attachments as $part) {
            if ($part->getFilename() === 'lorem.txt' && $part->getMediaType() === 'text' && $part->getMediaSubtype() === 'plain') {
                return true;
            }
        }

        return false;
    });
});

it('throw exception when file attachment does not exists', function (): void {
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
            return $this->view('emails.welcome')
                ->subject('Welcome with Attachment')
                ->attachment(dirname(__DIR__, 2) . '/fixtures/files/invalid.txt');
        }
    };

    Mail::to($to)->send($mailable);
})->throws(InvalidArgumentException::class);

it('run parallel task to send email', function (): void {
    $channel = new class () implements Channel {
        public function receive(?Cancellation $cancellation = null): mixed
        {
            return true;
        }

        public function send(mixed $data): void
        {
            //
        }

        public function close(): void
        {
            //
        }

        public function isClosed(): bool
        {
            return false;
        }

        public function onClose(Closure $onClose): void
        {
            //
        }
    };

    $cancellation = new class () implements Cancellation {
        public function subscribe(\Closure $callback): string
        {
            return 'id';
        }

        public function unsubscribe(string $id): void
        {

        }

        public function isRequested(): bool
        {
            return true;
        }

        public function throwIfRequested(): void
        {
            //
        }
    };

    $email = new Email();
    $email->from(faker()->freeEmail())
        ->to(faker()->freeEmail())
        ->subject('It will be sent in parallel')
        ->text('Welcome to the team');

    $task = new SendEmail($email, [
        'transport' => 'log',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    expect($task->run($channel, $cancellation))->toBeTruthy();
});

it('fail on sending email', function (): void {
    $channel = new class () implements Channel {
        public function receive(?Cancellation $cancellation = null): mixed
        {
            return true;
        }

        public function send(mixed $data): void
        {
            //
        }

        public function close(): void
        {
            //
        }

        public function isClosed(): bool
        {
            return true;
        }

        public function onClose(Closure $onClose): void
        {
            //
        }
    };

    $cancellation = new class () implements Cancellation {
        public function subscribe(\Closure $callback): string
        {
            return 'id';
        }

        public function unsubscribe(string $id): void
        {

        }

        public function isRequested(): bool
        {
            return true;
        }

        public function throwIfRequested(): void
        {
            //
        }
    };

    $email = new Email();
    $email->from(faker()->freeEmail())
        ->to(faker()->freeEmail())
        ->subject('It will fail')
        ->text('Welcome to the team');

    $task = new SendEmail($email, [
        'transport' => 'unsupported',
        'host' => 'smtp.server.com',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => 'username',
        'password' => 'password',
    ]);

    expect($task->run($channel, $cancellation))->toBeFalsy();
});

it('send email with custom headers', function (): void {
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
            return $this->view('emails.welcome')
                ->subject('Welcome with Headers')
                ->tagHeader('password-reset')
                ->metadataHeader('Color', 'blue')
                ->metadataHeader('Client-ID', '12345')
                ->textHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply')
                ->idHeader('References', [faker()->freeEmail(), faker()->freeEmail()]);
        }
    };

    Mail::to($to)->send($mailable);

    Mail::expect()->toBeSent($mailable, function (array $matches): bool {
        $email = $matches['email'] ?? null;

        if (! $email) {
            return false;
        }

        $headers = $email->getHeaders();
        $tag = $headers->get('X-Tag');
        $color = $headers->get('X-Metadata-Color');
        $client = $headers->get('X-Metadata-Client-ID');
        $text = $headers->get('X-Auto-Response-Suppress');
        $arrayId = $headers->get('References');

        return $tag && $tag->getValue() === 'password-reset'
            && $color && $color->getValue() === 'blue'
            && $client && $client->getValue() === '12345'
            && $text && $text->getValue() === 'OOF, DR, RN, NRN, AutoReply'
            && $arrayId !== null;
    });
});

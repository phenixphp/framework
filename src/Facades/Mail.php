<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Mail\Constants\MailerType;
use Phenix\Mail\Contracts\Mailable as MailableContract;
use Phenix\Mail\MailManager;
use Phenix\Runtime\Facade;
use Phenix\Testing\TestMail;

/**
 * @method static \Phenix\Mail\Contracts\Mailer mailer(MailerType|null $mailerType = null)
 * @method static \Phenix\Mail\Contracts\Mailer using(MailerType $mailerType)
 * @method static \Phenix\Mail\Contracts\Mailer to(array|string $to)
 * @method static void send(\Phenix\Mail\Contracts\Mailable $mailable)
 * @method static \Phenix\Mail\Contracts\Mailer fake(\Phenix\Mail\Constants\MailerType|null $mailerType = null)
 * @method static TestMail expect(MailableContract|string $mailable, MailerType|null $mailerType = null)
 *
 * @see \Phenix\Mail\MailManager
 */
class Mail extends Facade
{
    public static function getKeyName(): string
    {
        return MailManager::class;
    }

    public static function expect(MailableContract|string $mailable, MailerType|null $mailerType = null): TestMail
    {
        $mailerType ??= MailerType::from(Config::get('mail.default'));

        return new TestMail(
            $mailable,
            self::mailer($mailerType)->getSendingLog()
        );
    }
}

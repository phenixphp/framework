<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Amp\Future;
use Phenix\Mail\Constants\MailerType;
use Phenix\Mail\Contracts\Mailable as MailableContract;
use Phenix\Mail\Contracts\Mailer;
use Phenix\Mail\MailManager;
use Phenix\Runtime\Facade;
use Phenix\Testing\TestMail;

/**
 * @method static Mailer mailer(MailerType|null $mailerType = null)
 * @method static Mailer using(MailerType $mailerType)
 * @method static Mailer to(array|string $to)
 * @method static Future send(MailableContract $mailable)
 * @method static Mailer fake(MailerType|null $mailerType = null)
 * @method static array getSendingLog(MailerType|null $mailerType = null)
 * @method static void resetSendingLog(MailerType|null $mailerType = null)
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
        return new TestMail(
            $mailable,
            self::getSendingLog($mailerType)
        );
    }
}

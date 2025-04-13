<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Mail\MailManager;
use Phenix\Runtime\Facade;

/**
 * @method static \Phenix\Mail\Contracts\Mailer mailer(string|null $mailer = null)
 * @method static \Phenix\Mail\Contracts\Mailer using(string $mailer)
 * @method static \Phenix\Mail\Contracts\Mailer to(array|string $to)
 * @method static void send(\Phenix\Mail\Contracts\Mailable $mailable, array $data = [], \Closure|null $callback = null)
 * @method static \Phenix\Mail\Contracts\Mailer log(\Phenix\Mail\Constants\MailerDriver|null $mailer = null)
 *
 * @see \Phenix\Mail\MailManager
 */
class Mail extends Facade
{
    public static function getKeyName(): string
    {
        return MailManager::class;
    }
}

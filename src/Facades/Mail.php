<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Mail\Contracts\Mailer;
use Phenix\Mail\MailManager;
use Phenix\Runtime\Facade;

/**
 * @method static Mailer mailer(string|null $mailer = null)
 * @method static Mailer using(string $mailer)
 * @method static Mailer to(array|string $to)
 * @method static void send(Mailable $mailable, array $data = [], \Closure|null $callback = null)
 * @method static Mailer log(string|null $mailer = null)
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

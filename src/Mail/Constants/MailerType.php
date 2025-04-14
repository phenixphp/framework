<?php

declare(strict_types=1);

namespace Phenix\Mail\Constants;

enum MailerType: string
{
    case SMTP = 'smtp';
    case AMAZON_SES = 'ses';
    case RESEND = 'resend';
}

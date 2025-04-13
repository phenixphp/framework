<?php

declare(strict_types=1);

namespace Phenix\Mail\Constants;

enum MailerDriver: string
{
    case SMTP = 'smtp';
    case AMAZON_SES = 'ses';
    case RESEND = 'resend';
}

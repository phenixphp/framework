<?php

declare(strict_types=1);

namespace Phenix\Mail\Transports;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class LogTransport implements TransportInterface
{
    public function send(RawMessage $message, Envelope|null $envelope = null): ?SentMessage
    {
        return null;
    }

    public function __toString(): string
    {
        return 'LogTransport';
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Mail\Contracts;

use Phenix\Mail\Mailable;

interface Mailer
{
    public function to(array|string $to): self;

    public function cc(array|string $cc): self;

    public function bcc(array|string $bcc): self;

    public function send(Mailable $mailable): void;

    public function getSendingLog(): array;
}

<?php

declare(strict_types=1);

namespace Phenix\Mail\Contracts;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

interface Mailable
{
    public function from(Address|array|string $from): static;

    public function to(array|string $to): static;

    public function cc(array|string $cc): static;

    public function bcc(array|string $bcc): static;

    public function build(): self;

    public function toMail(): Email;
}

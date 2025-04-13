<?php

declare(strict_types=1);

namespace Phenix\Mail\Contracts;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

interface Mailable
{
    public function from(Address|array|string $from): self;

    public function to(array|string $to): self;

    public function cc(array|string $cc): self;

    public function bcc(array|string $bcc): self;

    public function build(): self;

    public function toMail(): Email;
}

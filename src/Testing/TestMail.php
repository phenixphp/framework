<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Closure;
use Phenix\Data\Collection;
use Phenix\Mail\Contracts\Mailable;

class TestMail
{
    public readonly Collection $log;

    public function __construct(array $log = [])
    {
        $this->log = Collection::fromArray($log);
    }

    public function toBeSent(Mailable|string $mailable, Closure|null $closure = null): void
    {
        if ($mailable instanceof Mailable) {
            $mailable = $mailable::class;
        }

        $matches = $this->log->filter(function (array $mail) use ($mailable): bool {
            return $mail['mailable'] === $mailable;
        });

        if ($closure) {
            expect($closure($matches->first()))->toBeTrue();
        } else {
            expect($matches)->not->toBeEmpty();
        }
    }

    public function toNotBeSent(Mailable|string $mailable, Closure|null $closure = null): void
    {
        if ($mailable instanceof Mailable) {
            $mailable = $mailable::class;
        }

        $matches = $this->log->filter(function (array $mail) use ($mailable): bool {
            return $mail['mailable'] === $mailable;
        });

        if ($closure) {
            expect($closure($matches->first()))->toBeFalse();
        } else {
            expect($matches)->toBeEmpty();
        }
    }

    public function toBeSentTimes(Mailable|string $mailable, int $times): void
    {
        if ($mailable instanceof Mailable) {
            $mailable = $mailable::class;
        }

        $matches = $this->log->filter(function (array $mail) use ($mailable): bool {
            return $mail['mailable'] === $mailable;
        });

        expect($matches)->toHaveCount($times);
    }
}

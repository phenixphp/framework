<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Closure;
use Phenix\Data\Collection;
use Phenix\Mail\Contracts\Mailable;
use PHPUnit\Framework\Assert;

class TestMail
{
    public readonly Collection $log;
    protected string $mailable;

    /**
     * @param array<int, array{mailable: string, success: bool, email: mixed, timestamp: float}> $log
     */
    public function __construct(Mailable|string $mailable, array $log = [])
    {
        if ($mailable instanceof Mailable) {
            $mailable = $mailable::class;
        }

        $this->mailable = $mailable;
        $this->log = Collection::fromArray($log);
    }

    public function toBeSent(Closure|null $closure = null): void
    {
        $matches = $this->filterByMailable($this->mailable);

        if ($closure) {
            Assert::assertTrue($closure($matches->first()));
        } else {
            Assert::assertNotEmpty($matches, "Failed asserting that mailable '{$this->mailable}' was sent at least once.");
        }
    }

    public function toNotBeSent(Closure|null $closure = null): void
    {
        $matches = $this->filterByMailable($this->mailable);

        if ($closure) {
            Assert::assertFalse($closure($matches->first()));
        } else {
            Assert::assertEmpty($matches, "Failed asserting that mailable '{$this->mailable}' was NOT sent.");
        }
    }

    public function toBeSentTimes(int $times): void
    {
        $matches = $this->filterByMailable($this->mailable);

        $count = $matches->count();

        Assert::assertCount($times, $matches, "Failed asserting that mailable '{$this->mailable}' was sent {$times} times. Actual: {$count}.");
    }

    private function filterByMailable(string $mailable): Collection
    {
        $filtered = [];

        foreach ($this->log as $record) {
            if (($record['mailable'] ?? null) === $mailable) {
                $filtered[] = $record;
            }
        }

        return Collection::fromArray($filtered);
    }
}

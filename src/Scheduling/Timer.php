<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Amp\Interval;
use Closure;

use function Amp\weakClosure;

class Timer
{
    protected Closure $closure;

    private float $interval;

    private bool $reference = true;

    private Interval|null $timer = null;

    public function __construct(
        Closure $closure
    ) {
        $this->closure = weakClosure($closure);
    }

    public function seconds(float $seconds): self
    {
        $this->interval = max(0.001, $seconds);

        return $this;
    }

    public function milliseconds(int $milliseconds): self
    {
        $this->interval = max(0.001, $milliseconds / 1000);

        return $this;
    }

    public function everySecond(): self
    {
        return $this->seconds(1);
    }

    public function everyTwoSeconds(): self
    {
        return $this->seconds(2);
    }

    public function everyFiveSeconds(): self
    {
        return $this->seconds(5);
    }

    public function everyTenSeconds(): self
    {
        return $this->seconds(10);
    }

    public function everyFifteenSeconds(): self
    {
        return $this->seconds(15);
    }

    public function everyThirtySeconds(): self
    {
        return $this->seconds(30);
    }

    public function everyMinute(): self
    {
        return $this->seconds(60);
    }

    public function everyTwoMinutes(): self
    {
        return $this->seconds(120);
    }

    public function everyFiveMinutes(): self
    {
        return $this->seconds(300);
    }

    public function everyTenMinutes(): self
    {
        return $this->seconds(600);
    }

    public function everyFifteenMinutes(): self
    {
        return $this->seconds(900);
    }

    public function everyThirtyMinutes(): self
    {
        return $this->seconds(1800);
    }

    public function hourly(): self
    {
        return $this->seconds(3600);
    }

    public function reference(): self
    {
        $this->reference = true;

        if ($this->timer) {
            $this->timer->reference();
        }

        return $this;
    }

    public function unreference(): self
    {
        $this->reference = false;

        if ($this->timer) {
            $this->timer->unreference();
        }

        return $this;
    }

    public function run(): self
    {
        $this->timer = new Interval($this->interval, $this->closure, $this->reference);

        return $this;
    }

    public function enable(): self
    {
        if ($this->timer) {
            $this->timer->enable();
        }

        return $this;
    }

    public function disable(): self
    {
        if ($this->timer) {
            $this->timer->disable();
        }

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->timer?->isEnabled() ?? false;
    }
}

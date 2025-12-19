<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Closure;
use Cron\CronExpression;
use Phenix\Util\Date;

use function Amp\weakClosure;
use function count;

class Scheduler
{
    protected Closure $closure;

    protected string $timezone = 'UTC';

    protected CronExpression|null $expression = null;

    public function __construct(
        Closure $closure
    ) {
        $this->closure = weakClosure($closure);
    }

    public function setCron(string $expression): self
    {
        return $this->setExpressionString($expression);
    }

    public function hourly(): self
    {
        return $this->setExpressionString('@hourly');
    }

    public function daily(): self
    {
        return $this->setExpressionString('@daily');
    }

    public function weekly(): self
    {
        return $this->setExpressionString('@weekly');
    }

    public function monthly(): self
    {
        return $this->setExpressionString('@monthly');
    }

    public function everyMinute(): self
    {
        return $this->setExpressionString('* * * * *');
    }

    public function everyFiveMinutes(): self
    {
        return $this->setExpressionString('*/5 * * * *');
    }

    public function everyTenMinutes(): self
    {
        return $this->setExpressionString('*/10 * * * *');
    }

    public function everyFifteenMinutes(): self
    {
        return $this->setExpressionString('*/15 * * * *');
    }

    public function everyThirtyMinutes(): self
    {
        return $this->setExpressionString('*/30 * * * *');
    }

    public function everyTwoHours(): self
    {
        return $this->setExpressionString('0 */2 * * *');
    }

    public function everyDay(): self
    {
        return $this->daily();
    }

    public function everyTwoDays(): self
    {
        return $this->setExpressionString('0 0 */2 * *');
    }

    public function everyWeekday(): self
    {
        return $this->setExpressionString('0 0 * * 1-5');
    }

    public function everyWeekend(): self
    {
        return $this->setExpressionString('0 0 * * 6,0');
    }

    public function mondays(): self
    {
        return $this->setExpressionString('0 0 * * 1');
    }

    public function fridays(): self
    {
        return $this->setExpressionString('0 0 * * 5');
    }

    public function dailyAt(string $time): self
    {
        return $this->daily()->at($time);
    }

    public function weeklyAt(string $time): self
    {
        return $this->weekly()->at($time);
    }

    public function everyWeekly(): self
    {
        return $this->weekly();
    }

    public function at(string $time): self
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        $expr = $this->expression?->getExpression() ?? '* * * * *';

        $parts = explode(' ', $expr);

        if (count($parts) === 5) {
            $parts[0] = (string) $minute;
            $parts[1] = (string) $hour;
        }

        $this->expression = new CronExpression(implode(' ', $parts));

        return $this;
    }

    public function timezone(string $tz): self
    {
        $this->timezone = $tz;

        return $this;
    }

    protected function setExpressionString(string $expression): self
    {
        $this->expression = new CronExpression($expression);

        return $this;
    }

    public function tick(Date|null $now = null): void
    {
        if (! $this->expression) {
            return;
        }

        $now ??= Date::now();
        $localNow = $now->copy()->timezone($this->timezone);

        if ($this->expression->isDue($localNow)) {
            ($this->closure)();
        }
    }
}

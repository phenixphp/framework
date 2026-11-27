<?php

declare(strict_types=1);

namespace Phenix\Views;

use Amp\Cancellation;
use Amp\Sync\LocalMutex;
use Closure;
use Fiber;
use Phenix\Views\Contracts\View;
use Throwable;

class BladeView implements View
{
    protected static LocalMutex|null $mutex = null;
    protected static Fiber|null $owner = null;
    protected static bool $rendering = false;

    public function __construct(
        protected Closure $bladeFactory,
        protected string $template,
        protected array $data = [],
        protected Cancellation|null $cancellation = null,
    ) {
    }

    public function render(Cancellation|null $cancellation = null): string
    {
        $cancellation ??= $this->cancellation;

        $fiber = Fiber::getCurrent();
        $nested = self::$rendering && self::$owner === $fiber;
        $lock = null;

        if (! $nested) {
            self::$mutex ??= new LocalMutex();
            $lock = self::$mutex->acquire();
            self::$owner = $fiber;
            self::$rendering = true;
        }

        $level = ob_get_level();

        try {
            $blade = ($this->bladeFactory)();
            $blade->setCancellation($cancellation);

            return $blade->run($this->template, $this->data);
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        } finally {
            if ($lock !== null) {
                self::$owner = null;
                self::$rendering = false;
                $lock->release();
            }
        }
    }

    public function __toString(): string
    {
        return $this->render();
    }
}

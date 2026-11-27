<?php

declare(strict_types=1);

namespace Phenix\Views;

use Amp\Sync\LocalMutex;
use Fiber;
use Phenix\Views\Contracts\View as ViewContract;
use Throwable;

class View implements ViewContract
{
    private static LocalMutex|null $mutex = null;

    private static Fiber|null $owner = null;

    private static bool $rendering = false;

    protected TemplateFactory $templateFactory;

    public function __construct(
        protected string $template,
        protected array $data = [],
        TemplateFactory|null $templateFactory = null,
        protected bool $handleLayout = true,
        protected string|null $name = null,
    ) {
        $this->template = $template;
        $this->data = $data;

        $this->templateFactory = $templateFactory ?? new TemplateFactory(new TemplateCache(new ViewsConfig()));
    }

    public function render(): string
    {
        $fiber = Fiber::getCurrent();
        $nested = self::$rendering && self::$owner === $fiber;
        $lock = null;

        if (! $nested) {
            self::$mutex ??= new LocalMutex();
            $lock = self::$mutex->acquire();
            self::$owner = $fiber;
            self::$rendering = true;
        }

        $obLevel = ob_get_level();
        $entered = false;

        try {
            $this->templateFactory->enter($this->template, $this->name ?? $this->template);
            $entered = true;
            ob_start();

            $data = $this->data;
            $data['_env'] = $this->templateFactory;

            (static function (string $__phenix_path, array $__phenix_data): void {
                extract($__phenix_data, EXTR_SKIP);

                require $__phenix_path;
            })($this->template, $data);

            $content = ob_get_clean();

            if ($this->handleLayout && $this->templateFactory->hasLayout()) {
                return $this->templateFactory->layout($this->data)->render();
            }

            return $content;
        } catch (Throwable $th) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw $th;
        } finally {
            if ($entered) {
                $this->templateFactory->leave();
            }

            if ($lock !== null) {
                $this->templateFactory->clear();
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

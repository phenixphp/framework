<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Views\Contracts\View as ViewContract;
use Throwable;

class View implements ViewContract
{
    protected string $template;
    protected array $data = [];
    protected TemplateFactory $templateFactory;

    public function __construct(
        string $template,
        array $data = [],
    ) {
        $this->template = $template;
        $this->data = $data;

        $this->templateFactory = new TemplateFactory(new ViewCache(new Config()));
    }

    public function render(): string
    {
        try {
            $obLevel = ob_get_level();

            ob_start();

            $path = $this->template;
            $data = $this->data;
            $data['_env'] = $this->templateFactory;

            (static function () use ($path, $data): void {
                extract($data, EXTR_SKIP);

                require $path;
            })();

            $content = ob_get_clean();

            if ($this->templateFactory->hasLayout()) {
                return $this->templateFactory->layout()->render();
            }

            $this->templateFactory->clear();

            return $content;
        } catch (Throwable $th) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw $th;
        }
    }

    public function __toString(): string
    {
        return $this->render();
    }
}

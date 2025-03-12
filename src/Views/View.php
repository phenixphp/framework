<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Views\Contracts\View as ViewContract;

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
        ob_start();

        (function (): void {
            $_env = $this->templateFactory;

            extract($this->data);

            require $this->template;
        })();

        $content = ob_get_clean();

        if ($this->templateFactory->hasLayout()) {
            return $this->templateFactory->layout()->render();
        }

        return $content;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}

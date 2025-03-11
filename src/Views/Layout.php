<?php

declare(strict_types=1);

namespace Phenix\Views;

class Layout extends View
{
    protected array $sections;

    public function __construct(
        string $template,
        array $data = [],
        array $sections = [],
    ) {
        parent::__construct($template, $data);

        foreach ($sections as $name => $value) {
            $this->templateFactory->startSection($name, $value);
        }
    }

    public function render(): string
    {
        ob_start();

        (function (): void {
            $_env = $this->templateFactory;

            extract($this->data);

            require $this->template;
        })();

        return ob_get_clean();
    }
}

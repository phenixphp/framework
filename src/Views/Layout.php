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
        TemplateFactory|null $templateFactory = null,
        string|null $name = null,
    ) {
        parent::__construct($template, $data, $templateFactory, true, $name);

        if ($templateFactory === null) {
            $this->templateFactory->inheritSections($sections);
        }
    }
}

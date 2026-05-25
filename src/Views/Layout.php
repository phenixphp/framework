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

        $this->templateFactory->inheritSections($sections);
    }
}

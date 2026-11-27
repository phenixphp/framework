<?php

declare(strict_types=1);

namespace Phenix\Views\Contracts;

use Closure;

interface TemplateEngine
{
    public function view(string $template, array $data = []): View;

    public function directive(string $name, Closure $closure): void;

    public function clearCache(): void;

    public function compile(string $template): void;
}

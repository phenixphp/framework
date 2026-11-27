<?php

declare(strict_types=1);

namespace Phenix\Views\Contracts;

use Amp\Cancellation;
use Closure;

interface TemplateEngine
{
    public function view(string $template, array $data = [], Cancellation|null $cancellation = null): View;

    public function directive(string $name, Closure $closure): void;

    public function clearCache(): void;

    public function compile(string $template, Cancellation|null $cancellation = null): void;
}

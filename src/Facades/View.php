<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Closure;
use Phenix\Runtime\Facade;
use Phenix\Views\Contracts\TemplateEngine;
use Phenix\Views\Contracts\View as ViewContract;

/**
 * @method static ViewContract view(string $template, array $data = [])
 * @method static void directive(string $name, Closure $closure)
 * @method static void clearCache()
 * @method static void compile(string $template)
 *
 * @see \Phenix\Views\TemplateEngine
 */
class View extends Facade
{
    public static function getKeyName(): string
    {
        return TemplateEngine::class;
    }
}

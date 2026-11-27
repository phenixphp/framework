<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Views\Exceptions\ViewNotFoundException;

abstract class TemplateRenderer
{
    public function __construct(protected TemplateCache $cache, protected TemplateEngine|null $engine = null)
    {
    }

    public function setEngine(TemplateEngine $engine): void
    {
        $this->engine = $engine;
    }

    abstract public function renderInclude(array $variables, string $template, array $data = []): string;

    public function renderIncludeIf(array $variables, string $template, array $data = []): string
    {
        try {
            $this->includeCompiler()->compile($template);
        } catch (ViewNotFoundException) {
            return '';
        }

        return $this->renderInclude($variables, $template, $data);
    }

    public function renderIncludeWhen(array $variables, bool $condition, string $template, array $data = []): string
    {
        return $condition ? $this->renderInclude($variables, $template, $data) : '';
    }

    public function renderIncludeFirst(array $variables, array $templates, array $data = []): string
    {
        foreach ($templates as $template) {
            try {
                $this->includeCompiler()->compile($template);
            } catch (ViewNotFoundException) {
                continue;
            }

            return $this->renderInclude($variables, $template, $data);
        }

        return '';
    }

    public function renderEach(array $variables, string $template, iterable $items, string $item, string|null $empty = null): string
    {
        $result = '';
        $hasItems = false;

        foreach ($items as $key => $value) {
            $hasItems = true;
            $result .= $this->renderInclude($variables, $template, [$item => $value, 'key' => $key]);
        }

        return ! $hasItems && $empty !== null ? $this->renderInclude($variables, $empty) : $result;
    }

    private function includeCompiler(): TemplateEngine
    {
        return $this->engine ??= new TemplateEngine(cache: $this->cache);
    }
}

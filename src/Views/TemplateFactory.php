<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Views\Contracts\View as ViewContract;

class TemplateFactory
{
    protected string|null $section;
    protected array $sections;
    protected string|null $layout;
    protected array $data;

    public function __construct(
        protected ViewCache $cache
    ) {
        $this->section = null;
        $this->layout = null;
    }

    public function extends(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->data = $data;
    }

    public function hasLayout(): bool
    {
        return $this->layout !== null;
    }

    public function layout(): Layout
    {
        return new Layout($this->cache->getCacheFilePath($this->layout), $this->data, $this->sections);
    }

    public function make(string $template, array $data = []): ViewContract
    {
        return new View(
            $this->cache->getCacheFilePath($template),
            $data,
        );
    }

    public function startSection(string $name, string|null $value = null): void
    {
        if ($value) {
            $this->sections[$name] = $value;
        } else {
            $this->section = $name;

            ob_start();

            $this->sections[$name] = null;
        }
    }

    public function endSection(): void
    {
        if ($this->section && $this->sections[$this->section] === null) {
            $buffer = ob_get_clean();

            $this->sections[$this->section] = trim($buffer) ?: '';
        }

        $this->section = null;
    }

    public function yieldSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function clear(): void
    {
        $this->layout = null;
        $this->sections = [];
        $this->section = null;
    }
}

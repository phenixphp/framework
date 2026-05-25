<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Views\Contracts\View as ViewContract;
use Stringable;

class TemplateFactory
{
    protected string|null $section;

    /**
     * @var array<string, string|Stringable|null>
     */
    protected array $sections;

    protected string|null $layout;

    protected array $data;

    public function __construct(
        protected TemplateCache $cache
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
        if ($value !== null) {
            $this->sections[$name] = new EscapedValue($value);

            return;
        }

        $this->section = $name;

        ob_start();

        $this->sections[$name] = null;
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
        return (string) ($this->sections[$name] ?? '');
    }

    /**
     * @param array<string, string|Stringable|null> $sections
     */
    public function inheritSections(array $sections): void
    {
        $this->sections = $sections;
    }

    public function clear(): void
    {
        $this->layout = null;
        $this->sections = [];
        $this->section = null;
    }
}

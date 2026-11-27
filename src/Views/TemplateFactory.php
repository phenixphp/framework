<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Exceptions\RuntimeException;
use Phenix\Views\Contracts\View as ViewContract;

use function array_key_exists;
use function in_array;
use function is_int;

class TemplateFactory extends TemplateRenderer
{
    /** @var array<string, string|EscapedValue> */
    protected array $sections = [];

    protected string|null $layout = null;

    protected array $data = [];

    private array $sectionStack = [];

    private array $stacks = [];

    private array $stackFrames = [];

    private array $once = [];

    private array $active = [];

    private const PARENT_MARKER = '<!--PHENIX_PARENT_SECTION-->';

    public function extends(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->data = $data;
    }

    public function hasLayout(): bool
    {
        return $this->layout !== null;
    }

    public function layout(array $variables = []): Layout
    {
        $layout = $this->layout;
        $this->layout = null;
        $this->compiler()->compile($layout);

        return new Layout($this->cache->getCacheFilePath($layout), array_merge($variables, $this->data), [], $this, $layout);
    }

    public function make(string $template, array $data = []): ViewContract
    {
        $this->compiler()->compile($template);

        return new View($this->cache->getCacheFilePath($template), $data, $this, true, $template);
    }

    public function startSection(string $name, string|null $value = null): void
    {
        if ($value !== null) {
            if (! array_key_exists($name, $this->sections)) {
                $this->sections[$name] = new EscapedValue($value);
            }

            return;
        }

        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(bool $overwrite = false): void
    {
        $name = array_pop($this->sectionStack);

        if ($name === null) {
            throw new RuntimeException('No section is being rendered.');
        }

        $content = trim(ob_get_clean());

        $this->sections[$name] = ! $overwrite && array_key_exists($name, $this->sections)
            ? str_replace(self::PARENT_MARKER, $content, (string) $this->sections[$name])
            : $content;
    }

    public function showSection(): string
    {
        $name = end($this->sectionStack);

        $this->endSection();

        return $this->yieldSection($name);
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return str_replace(self::PARENT_MARKER, '', (string) ($this->sections[$name] ?? $default));
    }

    public function parentSection(): string
    {
        return self::PARENT_MARKER;
    }

    public function hasSection(string $name): bool
    {
        return $this->yieldSection($name) !== '';
    }

    public function inheritSections(array $sections): void
    {
        $this->sections = $sections;
    }

    public function renderInclude(array $variables, string $template, array $data = []): string
    {
        unset($variables['_env'], $variables['__phenix_data'], $variables['__phenix_path']);

        $this->compiler()->compile($template);

        return (new View($this->cache->getCacheFilePath($template), array_merge($variables, $data), $this, false, $template))->render();
    }

    public function startStack(string $type, string $name): void
    {
        $this->stackFrames[] = [$type, $name];

        ob_start();
    }

    public function endStack(): void
    {
        $frame = array_pop($this->stackFrames);

        if ($frame === null) {
            throw new RuntimeException('No stack is being rendered.');
        }

        [$type, $name] = $frame;

        $content = ob_get_clean();

        if ($type === 'pushOnce' && isset($this->once[$name])) {
            return;
        }

        if ($type === 'pushOnce') {
            $this->once[$name] = true;
        }

        $current = $this->stacks[$name] ?? '';

        $this->stacks[$name] = $type === 'prepend' ? $content . $current : $current . $content;
    }

    public function yieldStack(string $name, string $default = ''): string
    {
        return $this->stacks[$name] ?? $default;
    }

    public function classAttribute(array $classes): string
    {
        $result = [];

        foreach ($classes as $class => $include) {
            if (is_int($class)) {
                $result[] = $include;
            } elseif ($include) {
                $result[] = $class;
            }
        }

        return 'class="' . e(implode(' ', $result)) . '"';
    }

    public function enter(string $path, string $name): void
    {
        if (in_array($path, array_column($this->active, 'path'), true)) {
            throw new RuntimeException('Circular view dependency: ' . implode(' -> ', [...array_column($this->active, 'name'), $name]));
        }

        $this->active[] = ['path' => $path, 'name' => $name];
    }

    public function leave(): void
    {
        array_pop($this->active);
    }

    public function clear(): void
    {
        $this->layout = null;
        $this->sections = [];
        $this->sectionStack = [];
        $this->stacks = [];
        $this->stackFrames = [];
        $this->once = [];
        $this->active = [];
    }

    private function compiler(): TemplateEngine
    {
        return $this->engine ??= new TemplateEngine(cache: $this->cache);
    }
}

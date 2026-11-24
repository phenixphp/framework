<?php

declare(strict_types=1);

namespace Phenix\Views;

use Closure;
use Phenix\Views\Contracts\TemplateEngine as TemplateEngineContract;
use Phenix\Views\Contracts\View as ViewContract;

class TemplateEngine implements TemplateEngineContract
{
    /** @var array<string, Closure> */
    protected array $directives = [];

    public function __construct(
        protected TemplateCache $cache = new TemplateCache(),
    ) {
    }

    public function view(string $template, array $data = []): ViewContract
    {
        $this->compile($template);

        return new BladeView(fn (): BladeEngine => $this->makeBlade(), $template, $data);
    }

    public function directive(string $name, Closure $closure): void
    {
        $this->directives[$name] = $closure;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }

    public function compile(string $template): void
    {
        $this->makeBlade()->compile($template);
    }

    protected function makeBlade(): BladeEngine
    {
        $blade = new BladeEngine($this->cache);

        foreach ($this->directives as $name => $directive) {
            $blade->directive($name, $directive);
        }

        return $blade;
    }
}

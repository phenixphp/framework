<?php

declare(strict_types=1);

namespace Phenix\Views;

use Closure;
use Phenix\Facades\File;
use Phenix\Views\Contracts\TemplateEngine as TemplateEngineContract;
use Phenix\Views\Contracts\View as ViewContract;
use Phenix\Views\Exceptions\ViewNotFoundException;

class TemplateEngine implements TemplateEngineContract
{
    protected TemplateFactory $templateFactory;

    protected array $sections = [];

    public function __construct(
        protected TemplateCompiler $compiler = new TemplateCompiler(),
        protected TemplateCache $cache = new TemplateCache(),
        TemplateFactory|null $templateFactory = null
    ) {
        $this->templateFactory = $templateFactory ?? new TemplateFactory($this->cache);
    }

    public function view(string $template, array $data = []): ViewContract
    {
        $factory = clone $this->templateFactory;
        $factory->clear();
        $factory->setEngine($this);

        return $factory->make($template, $data);
    }

    public function directive(string $name, Closure $closure): void
    {
        $this->compiler->registerDirective($name, $closure);
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }

    public function compile(string $template): void
    {
        $filePath = File::realPath($this->cache->getSourcePath($template));
        $basePath = File::realPath($this->cache->getViewPath($template));

        if ($filePath === false || $basePath === false || ! str_starts_with($filePath, rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            throw new ViewNotFoundException("Template {$template} not found.");
        }

        if (! $this->cache->isCached($template)) {
            $content = File::get($filePath);

            $compiler = clone $this->compiler;
            $compiled = $compiler->compile($content);

            $this->cache->put($template, $compiled);
        }
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Views;

use Closure;
use Phenix\Views\Exceptions\ViewNotFoundException;
use Phenix\Facades\File;
use Phenix\Views\Contracts\TemplateEngine as TemplateEngineContract;
use Phenix\Views\Contracts\View as ViewContract;

class TemplateEngine implements TemplateEngineContract
{
    protected TemplateFactory $templateFactory;

    protected array $sections = [];

    public function __construct(
        protected TemplateCompiler $compiler = new TemplateCompiler(),
        protected ViewCache $cache = new ViewCache(),
        TemplateFactory|null $templateFactory = null
    ) {
        $this->templateFactory = $templateFactory ?? new TemplateFactory($this->cache);
    }

    public function view(string $template, array $data = []): ViewContract
    {
        $this->compile($template);

        while ($this->compiler->hasTemplates()) {
            $this->compile($this->compiler->dequeueTemplate());
        }

        return $this->templateFactory->make($template, $data);
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
        $filePath = realpath($this->cache->getSourcePath($template));
        $basePath = realpath($this->cache->getViewPath());

        if ($filePath === false || ! str_starts_with($filePath, $basePath)) {
            throw new ViewNotFoundException("Template {$template} not found.");
        }

        if (! $this->cache->isCached($template)) {
            $content = File::get($filePath);

            $compiled = $this->compiler->compile($content);

            $this->cache->put($template, $compiled);
        }
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Views;

use Closure;
use Phenix\Exceptions\Views\ViewNotFoundException;
use Phenix\Facades\File;
use Phenix\Views\Contracts\View as ViewContract;

class TemplateEngine
{
    protected ViewCache $cache;
    protected TemplateCompiler $compiler;
    protected Config $config;
    protected TemplateFactory $templateFactory;

    protected array $sections = [];

    public function __construct()
    {
        $this->config = new Config();
        $this->compiler = new TemplateCompiler();
        $this->cache = new ViewCache($this->config);
        $this->templateFactory = new TemplateFactory($this->cache);
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

    protected function compile(string $template): void
    {
        $file = ViewName::ensure($template);

        $filePath = realpath($this->config->path($file));
        $basePath = realpath($this->config->path());

        if ($filePath === false || ! str_starts_with($filePath, $basePath)) {
            throw new ViewNotFoundException("Template {$file} not found.");
        }

        if (! $this->cache->isCached($template)) {
            $content = File::get($filePath);

            $compiled = $this->compiler->compile($content);

            $this->cache->put($template, $compiled);
        }
    }
}

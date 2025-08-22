<?php

declare(strict_types=1);

namespace Phenix\Views;

use Closure;

use function array_shift;

class TemplateCompiler
{
    /**
     * @var array<string, string>
     */
    protected array $directives = [];

    /**
     * @var array<int, string>
     */
    protected array $customDirectives = [];

    /**
     * @var array<string, string|null>
     */
    protected array $sections = [];

    /**
     * @var array<int, string>
     */
    protected array $templates = [];

    public function __construct()
    {
        $this->templates = [];
        $this->directives = [
            '@if' => '<?php if ',
            '@elseif' => '<?php elseif ',
            '@else' => '<?php else: ?>',
            '@endif' => '<?php endif; ?>',
            '@foreach' => '<?php foreach',
            '@endforeach' => '<?php endforeach; ?>',
            '@extends' => '<?php $_env->extends',
            '@section' => '<?php $_env->startSection',
            '@endsection' => '<?php $_env->endSection(); ?>',
            '@yield' => '<?= $_env->yieldSection',
            '@include' => '<?= $_env->make',
        ];
        $this->customDirectives = [];
    }

    public function registerDirective(string $name, Closure $closure): void
    {
        $this->customDirectives[$name] = $closure;
    }

    public function compile(string $content): string
    {
        $this->extractTemplates($content);

        foreach ($this->directives as $key => $replace) {
            $content = preg_replace_callback("/$key\s*(\([^)]*\))?/", function ($matches) use ($key, $replace): string {
                return match($key) {
                    '@if', '@elseif', '@foreach' => "{$replace}{$matches[1]}: ?>",
                    '@extends', '@include', '@section', '@yield' => "{$replace}{$matches[1]}; ?>",
                    default => $replace,
                };
            }, $content);
        }

        foreach ($this->customDirectives as $name => $callback) {
            $pattern = "/@{$name}(?:\((.*?)\))?/";

            $content = preg_replace_callback($pattern, function ($matches) use ($callback): string {
                return isset($matches[1]) ? $callback($matches[1]) : $callback();
            }, $content);
        }

        $content = preg_replace('/{{\s*(.+?)\s*}}/', '<?= e($1) ?>', $content);
        $content = preg_replace('/{!!\s*(.+?)\s*!!}/', '<?= $1 ?>', $content);

        return $content;
    }

    public function hasTemplates(): bool
    {
        return count($this->templates) > 0;
    }

    public function dequeueTemplate(): string
    {
        return array_shift($this->templates);
    }

    private function extractTemplates(string $content): void
    {
        preg_match_all('/@(?:extends|include)\s*\(\s*[\'"](.+?)[\'"](?:\s*,.*?)?\s*\)/', $content, $matches);

        $this->templates = array_merge($this->templates, $matches[1]);
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Views;

class TemplateCompiler
{
    protected array $directives = [];
    protected array $sections = [];
    protected string|null $layout = null;

    public function __construct()
    {
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
            '@yield' => '<?php echo $_env->yieldSection',
            '@include' => '<?php echo $_env->make',
        ];
    }

    public function directive(string $name, callable $callback): void
    {
        $this->directives[$name] = $callback;
    }

    public function compile(string $content): string
    {
        $this->layout = $this->extractLayoutName($content);

        foreach ($this->directives as $key => $replace) {
            $content = preg_replace_callback("/$key\s*(\([^)]*\))?/", function ($matches) use ($key, $replace) {
                return match($key) {
                    '@if', '@elseif', '@foreach' => "{$replace}{$matches[1]}: ?>",
                    '@extends', '@include', '@section', '@yield' => "{$replace}{$matches[1]}; ?>",
                    default => $replace,
                };
            }, $content);
        }

        $content = preg_replace('/{{\s*(.+?)\s*}}/', '<?= e($1) ?>', $content);
        $content = preg_replace('/{!!\s*(.+?)\s*!!}/', '<?= $1 ?>', $content);

        return $content;
    }

    public function hasLayout(): bool
    {
        return $this->layout !== null;
    }

    public function getLayoutName(): string
    {
        return $this->layout;
    }

    private function extractLayoutName(string $content): string|null
    {
        preg_match('/@extends\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*\[.*\])?\s*\)/', $content, $matches);

        return $matches[1] ?? null;
    }
}

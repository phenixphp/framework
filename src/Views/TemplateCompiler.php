<?php

declare(strict_types=1);

namespace Phenix\Views;

use Closure;
use Phenix\Exceptions\RuntimeException;

use function count;
use function is_array;
use function strlen;

class TemplateCompiler
{
    /** @var array<string, Closure> */
    protected array $customDirectives = [];
    private int $loopId = 0;
    /** @var list<int> */
    private array $forelse = [];

    public function registerDirective(string $name, Closure $closure): void
    {
        $this->customDirectives[$name] = $closure;
    }

    public function compile(string $content): string
    {
        $this->forelse = [];
        $this->loopId = 0;
        $result = '';

        foreach (token_get_all($content) as $token) {
            if (is_array($token)) {
                $result .= $token[0] === T_INLINE_HTML ? $this->compileText($token[1]) : $token[1];
            } else {
                $result .= $token;
            }
        }

        return $result;
    }

    private function compileText(string $text): string
    {
        $result = '';
        $length = strlen($text);

        for ($offset = 0; $offset < $length;) {
            $result .= $this->compileTextFragment($text, $offset);
        }

        return $result;
    }

    private function compileTextFragment(string $text, int &$offset): string
    {
        $literal = $this->compileLiteral($text, $offset);

        if ($literal !== null) {
            return $literal;
        }

        $echo = $this->compileEcho($text, $offset);

        if ($echo !== null) {
            return $echo;
        }

        return $text[$offset] === '@' && preg_match('/\G@([A-Za-z](?:(?=[\x00-\x7f])\w)*)/A', $text, $match, 0, $offset)
            ? $this->compileTextDirective($text, $offset, $match)
            : $text[$offset++];
    }

    private function compileLiteral(string $text, int &$offset): string|null
    {
        if (substr($text, $offset, 4) === '{{--') {
            $end = strpos($text, '--}}', $offset + 4);

            if ($end !== false) {
                $offset = $end + 4;

                return '';
            }
        }

        if (substr($text, $offset, 2) === '@@') {
            $offset += 2;

            return '@';
        }

        return null;
    }

    private function compileEcho(string $text, int &$offset): string|null
    {
        if (substr($text, $offset, 3) === '{!!') {
            return $this->compileDelimitedEcho($text, $offset, 3, '!!}', false);
        }

        if (substr($text, $offset, 2) === '{{') {
            return $this->compileDelimitedEcho($text, $offset, 2, '}}', true);
        }

        return null;
    }

    private function compileDelimitedEcho(string $text, int &$offset, int $openingLength, string $closing, bool $escaped): string|null
    {
        $end = strpos($text, $closing, $offset + $openingLength);

        if ($end === false) {
            return null;
        }

        $expression = trim(substr($text, $offset + $openingLength, $end - $offset - $openingLength));
        $offset = $end + strlen($closing);

        return $escaped ? "<?= e({$expression}) ?>" : "<?= {$expression} ?>";
    }

    /** @param array<int, string> $match */
    private function compileTextDirective(string $text, int &$offset, array $match): string
    {
        $name = $match[1];
        $end = $offset + strlen($match[0]);
        $argumentStart = $end;
        $length = strlen($text);

        while ($argumentStart < $length && ($text[$argumentStart] === ' ' || $text[$argumentStart] === "\t")) {
            $argumentStart++;
        }

        $expression = null;

        if ($argumentStart < $length && $text[$argumentStart] === '(') {
            [$expression, $end] = $this->readExpression($text, $argumentStart);
        }

        if ($name === 'php' && $expression === null) {
            return $this->compilePhpBlock($text, $offset, $end);
        }

        $compiled = $this->compileDirective($name, $expression);
        $result = $compiled ?? substr($text, $offset, $end - $offset);
        $offset = $end;

        if ($name === 'switch' && $compiled !== null) {
            while ($offset < $length && ctype_space($text[$offset])) {
                $offset++;
            }
        }

        return $result;
    }

    private function compilePhpBlock(string $text, int &$offset, int $start): string
    {
        $end = strpos($text, '@endphp', $start);

        if ($end === false) {
            throw new RuntimeException('Unclosed @php directive.');
        }

        $content = substr($text, $start, $end - $start);
        $offset = $end + strlen('@endphp');

        return "<?php {$content} ?>";
    }

    /** @return array{string, int} */
    private function readExpression(string $text, int $start): array
    {
        $depth = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
            } elseif ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')' && --$depth === 0) {
                return [substr($text, $start + 1, $i - $start - 1), $i + 1];
            }
        }

        throw new RuntimeException('Unclosed directive expression.');
    }

    private function compileDirective(string $name, string|null $expression): string|null
    {
        if (isset($this->customDirectives[$name])) {
            return $expression === null ? ($this->customDirectives[$name])() : ($this->customDirectives[$name])($expression);
        }

        if ($name === 'forelse' || $name === 'endforelse' || ($name === 'empty' && $expression === null && $this->forelse !== [])) {
            return $this->compileForelseDirective($name, $expression);
        }

        $e = $expression ?? '';

        return match ($name) {
            'if', 'elseif', 'foreach', 'for', 'while', 'switch' => "<?php {$name} ({$e}): ?>",
            'isset', 'empty' => "<?php if ({$name}({$e})): ?>",
            'unless' => "<?php if (! ({$e})): ?>",
            'hasSection' => "<?php if (\$_env->hasSection({$e})): ?>",
            'else' => '<?php else: ?>',
            'endif', 'endunless', 'endisset', 'endempty' => '<?php endif; ?>',
            'endforeach', 'endfor', 'endwhile', 'endswitch' => "<?php {$name}; ?>",
            'case' => "<?php case {$e}: ?>",
            'default' => '<?php default: ?>',
            'break', 'continue' => $expression === null ? "<?php {$name}; ?>" : "<?php if ({$e}) {$name}; ?>",
            'extends' => "<?php \$_env->extends({$e}); ?>",
            'section' => "<?php \$_env->startSection({$e}); ?>",
            'endsection', 'stop' => '<?php $_env->endSection(); ?>',
            'show' => '<?php echo $_env->showSection(); ?>',
            'overwrite' => '<?php $_env->endSection(true); ?>',
            'yield' => "<?= \$_env->yieldSection({$e}) ?>",
            'parent' => '<?= $_env->parentSection() ?>',
            'include' => "<?= \$_env->renderInclude(get_defined_vars(), {$e}) ?>",
            'includeIf' => "<?= \$_env->renderIncludeIf(get_defined_vars(), {$e}) ?>",
            'includeWhen' => "<?= \$_env->renderIncludeWhen(get_defined_vars(), {$e}) ?>",
            'includeFirst' => "<?= \$_env->renderIncludeFirst(get_defined_vars(), {$e}) ?>",
            'each' => "<?= \$_env->renderEach(get_defined_vars(), {$e}) ?>",
            'push', 'prepend', 'pushOnce' => "<?php \$_env->startStack('{$name}', {$e}); ?>",
            'endpush', 'endprepend', 'endPushOnce', 'endpushOnce' => '<?php $_env->endStack(); ?>',
            'stack' => "<?= \$_env->yieldStack({$e}) ?>",
            'checked', 'selected', 'disabled', 'readonly', 'required' => "<?= ({$e}) ? '{$name}' : '' ?>",
            'class' => "<?= \$_env->classAttribute({$e}) ?>",
            'json' => "<?= json_encode({$e}, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>",
            'php', 'set' => "<?php {$e}; ?>",
            'unset' => "<?php unset({$e}); ?>",
            'method' => "<input type=\"hidden\" name=\"_method\" value=\"<?= e({$e}) ?>\">",
            default => null,
        };
    }

    private function compileForelseDirective(string $name, string|null $expression): string
    {
        if ($name === 'forelse') {
            $id = ++$this->loopId;
            $this->forelse[] = $id;
            $expression ??= '';

            return "<?php \$__phenix_empty_{$id} = true; foreach ({$expression}): \$__phenix_empty_{$id} = false; ?>";
        }

        if ($name === 'empty') {
            $id = $this->forelse[count($this->forelse) - 1];

            return "<?php endforeach; if (\$__phenix_empty_{$id}): ?>";
        }

        array_pop($this->forelse);

        return '<?php endif; ?>';
    }
}

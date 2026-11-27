<?php

declare(strict_types=1);

use Amp\DeferredFuture;
use Amp\File\File as AmpFile;
use Phenix\App;
use Phenix\Exceptions\RuntimeException;
use Phenix\Facades\View;
use Phenix\Filesystem\Contracts\File as FileContract;
use Phenix\Filesystem\File;
use Phenix\Packages\PackageRegistry;
use Phenix\Views\Exceptions\ViewNotFoundException;
use Phenix\Views\TemplateCache;
use Phenix\Views\TemplateCompiler;
use Phenix\Views\TemplateEngine;
use Phenix\Views\TemplateFactory;
use Phenix\Views\ViewsConfig;

it('preserves literal text while compiling comments, echoes and directives', function (): void {
    $compiler = new TemplateCompiler();
    $compiled = $compiler->compile(<<<'BLADE'
{{-- comment --}}@@literal {{ $name }} {!! $html !!} @unknown('value')
@if (str_contains($name, ')'))yes@endif
@php $value = 'ok'; @endphp
BLADE);

    expect($compiled)->not->toContain('comment')
        ->toContain('@literal', '<?= e($name) ?>', '<?= $html ?>', "@unknown('value')")
        ->toContain("if (str_contains(\$name, ')')):", "\$value = 'ok';");
});

it('reports unclosed PHP directive blocks', function (): void {
    expect(fn () => (new TemplateCompiler())->compile('@php $value = 1;'))
        ->toThrow(RuntimeException::class, 'Unclosed @php directive.');
});

it('handles escaped quotes and backslashes in directive expressions', function (): void {
    $source = <<<'BLADE'
@if (str_contains($value, 'it\'s (fine)') && str_contains($value, "path\\\\"))yes@endif
BLADE;

    $compiled = (new TemplateCompiler())->compile($source);

    expect($compiled)->toContain(<<<'PHP'
if (str_contains($value, 'it\'s (fine)') && str_contains($value, "path\\\\")):
PHP);
});

it('recognizes directive names using the original ASCII characters', function (): void {
    $compiler = new TemplateCompiler();
    $compiler->registerDirective('word_123', fn (): string => 'matched');

    expect($compiler->compile('@word_123 @word_123é @_word_123 @9word'))
        ->toBe('matched matchedé @_word_123 @9word');
});

it('renders a namespaced package view', function (): void {
    $path = base_path('storage/framework/testing/package-views-' . uniqid());

    mkdir($path, 0755, true);
    file_put_contents("{$path}/index.php", '<h1>{{ $title }}</h1>');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addViewPaths($path, 'blog');

    $template = new TemplateEngine();
    $output = $template->view('blog::index', ['title' => 'Package view'])->render();

    expect($output)->toContain('Package view');

    unlink("{$path}/index.php");
    rmdir($path);
});

it('render a template successfully', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('welcome', [
        'title' => 'Welcome',
        'colors' => ['red', 'green', 'blue'],
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Welcome');
    expect($output)->toContain('red');
    expect($output)->toContain('green');
    expect($output)->toContain('blue');
});

it('render a template in a specific directory successfully', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('users.index', [
        'title' => 'Users',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Users');
});

it('render a template including partial', function (): void {
    $token = 'abcd123';

    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('users.create', [
        'title' => 'Create user',
        'token' => $token,
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('form');
    expect($output)->toContain($token);
});

it('throw exception when template not found', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    $template->view('missing')->render();
})->throws(ViewNotFoundException::class);

it('register custom directive', function (): void {
    $action = 'You can create it';

    $template = new TemplateEngine();
    $template->clearCache();

    $template->directive('can', function (string $action): string {
        return "<?php if({$action} === 'create'): ?>";
    });
    $template->directive('endcan', function (): string {
        return "<?php endif; ?>";
    });

    $output = $template->view('invoice', [
        'title' => 'Create invoices',
        'action' => $action,
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain($action);
});

it('throw exception when template has errors', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    $template->view('invalid_content')->render();
})->throws(Exception::class);

it('overwrite an expired template in cache', function (): void {
    // Precompile the view
    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('users.index', [
        'title' => 'Previous title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Previous title');

    $template = new TemplateEngine();

    $output = $template->view('users.index', [
        'title' => 'New title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('New title');
});

it('escapes XSS in inline section value', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('users.index', [
        'title' => '<script>alert("xss")</script>',
    ])->render();

    expect($output)->toBeString();
    expect($output)->not->toContain('<script>');
    expect($output)->toContain('&lt;script&gt;');
});

it('escapes inline section values in template factory', function (): void {
    $factory = new TemplateFactory(new TemplateCache());

    $factory->startSection('title', '<script>alert("xss")</script>');

    expect($factory->yieldSection('title'))
        ->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
});

it('preserves rendered block section html in template factory', function (): void {
    $factory = new TemplateFactory(new TemplateCache());

    $factory->startSection('content');
    echo '<h1>Users</h1>';
    $factory->endSection();

    expect($factory->yieldSection('content'))->toBe('<h1>Users</h1>');
});

it('stores empty and zero inline section values without starting a buffer', function (): void {
    $factory = new TemplateFactory(new TemplateCache());

    $factory->startSection('empty', '');
    $factory->startSection('zero', '0');

    expect($factory->yieldSection('empty'))->toBe('');
    expect($factory->yieldSection('zero'))->toBe('0');
});

it('render template using facade', function (): void {
    View::clearCache();

    $output = View::view('users.index', [
        'title' => 'New title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('New title');
});

it('register custom directive using facade', function (): void {
    $action = 'You can create it';

    View::clearCache();

    View::directive('can', function (string $action): string {
        return "<?php if({$action} === 'create'): ?>";
    });

    View::directive('endcan', function (): string {
        return "<?php endif; ?>";
    });

    $output = View::view('invoice', [
        'title' => 'Create invoices',
        'action' => $action,
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain($action);
});


it('uses distinct cache files for package and application view names', function (): void {
    $config = new ViewsConfig();

    expect($config->cachePath('blog::index'))->not->toBe($config->cachePath('vendor.blog.index'));
});

it('renders nested expressions, conditional includes and stacks', function (): void {
    $config = new ViewsConfig();
    $source = $config->path('phenix-directive-test');
    $partial = $config->path('phenix-directive-partial');
    file_put_contents($source, <<<'BLADE'
@push('scripts')first@endpush
@prepend('scripts')before@endprepend
@pushOnce('scripts')once@endPushOnce
@pushOnce('scripts')ignored@endPushOnce
@if (count(array_filter($items, fn ($item) => $item > 1)) > 0)
    @includeWhen(true, $partial, ['message' => 'dynamic'])
@endif
@includeIf('missing-optional-view')
@forelse ($empty as $item)unexpected@empty empty-result @endforelse
@switch ($value)
    @case (1)one @break
    @default other
@endswitch
@stack('scripts')
BLADE);
    file_put_contents($partial, '{{ $message }}');

    try {
        $output = (new TemplateEngine())->view('phenix-directive-test', [
            'items' => [1, 2], 'empty' => [], 'value' => 1, 'partial' => 'phenix-directive-partial',
        ])->render();
        expect($output)->toContain('dynamic', 'empty-result', 'one', 'beforefirstonce')
            ->not->toContain('unexpected', 'ignored', 'other');
    } finally {
        unlink($source);
        unlink($partial);
        View::clearCache();
    }
});

it('renders parent sections with the child view content', function (): void {
    $config = new ViewsConfig();
    $parent = $config->path('phenix-parent-test');
    $child = $config->path('phenix-child-test');
    file_put_contents($parent, "@section('sidebar')parent@show");
    file_put_contents($child, "@extends('phenix-parent-test') @section('sidebar')@parent child@endsection");

    try {
        expect((new TemplateEngine())->view('phenix-child-test')->render())->toContain('parent child');
    } finally {
        unlink($parent);
        unlink($child);
        View::clearCache();
    }
});

it('detects circular view inclusions', function (): void {
    $source = (new ViewsConfig())->path('phenix-cycle-test');
    file_put_contents($source, "@include('phenix-cycle-test')");

    try {
        expect(fn () => (new TemplateEngine())->view('phenix-cycle-test')->render())
            ->toThrow(RuntimeException::class, 'phenix-cycle-test -> phenix-cycle-test');
    } finally {
        unlink($source);
        View::clearCache();
    }
});

it('renders conditional attributes, JSON, PHP and loop directives', function (): void {
    $source = (new ViewsConfig())->path('phenix-flow-test');
    file_put_contents($source, <<<'BLADE'
@php($total = 0)
@for ($i = 0; $i < 3; $i++)
    @continue($i === 1)
    @set($total += $i)
@endfor
@while ($total < 3) @php($total++) @endwhile
@unless ($total !== 3) correct @endunless
@isset ($message){{ $message }}@endisset
@empty ($missing) empty @endempty
<input @checked(true) @selected(false) @disabled(true) @readonly(true) @required(true) @class(['base', 'active' => true, 'off' => false])>
<script>@json($payload)</script>
@method('PUT')
BLADE);

    try {
        $view = (new TemplateEngine())->view('phenix-flow-test', [
            'message' => '<unsafe>', 'payload' => ['value' => '</script>'],
        ]);
        $output = $view->render();
        expect($output)->toContain('correct', '&lt;unsafe&gt;', 'empty', 'checked', 'disabled', 'readonly', 'required', 'class="base active"', '\\u003C', 'value="PUT"')
            ->not->toContain('selected', 'class="base active off"');
    } finally {
        unlink($source);
        View::clearCache();
    }
});

it('does not retain stacks across repeated renders', function (): void {
    $source = (new ViewsConfig())->path('phenix-repeat-test');
    file_put_contents($source, "@push('scripts')item@endpush @stack('scripts')");

    try {
        $view = (new TemplateEngine())->view('phenix-repeat-test');
        expect($view->render())->toBe($view->render());
    } finally {
        unlink($source);
        View::clearCache();
    }
});

it('executes a compiled view on each render and each repeated include', function (): void {
    $config = new ViewsConfig();
    $source = $config->path('phenix-repeated-view');
    $partial = $config->path('phenix-repeated-partial');
    file_put_contents($source, "@include('phenix-repeated-partial')@include('phenix-repeated-partial')");
    file_put_contents($partial, 'item');

    try {
        $view = (new TemplateEngine())->view('phenix-repeated-view');
        expect($view->render())->toBe('itemitem')
            ->and($view->render())->toBe('itemitem');
    } finally {
        unlink($source);
        unlink($partial);
        View::clearCache();
    }
});

it('uses the compiled file context when rendering a view', function (): void {
    $config = new ViewsConfig();
    $name = 'phenix-file-context-test';
    $source = $config->path($name);
    file_put_contents($source, '@php echo __FILE__ . "|" . __DIR__; @endphp');

    try {
        $output = (new TemplateEngine())->view($name)->render();
        expect($output)->toContain($config->cachePath($name), dirname($config->cachePath($name)));
    } finally {
        unlink($source);
        View::clearCache();
    }
});

it('isolates concurrent renders when a template suspends', function (): void {
    $source = (new ViewsConfig())->path('phenix-concurrent-test');
    file_put_contents($source, <<<'BLADE'
{{ $name }}-start
@php($started->complete())
@php($resume->getFuture()->await())
{{ $name }}-end
BLADE);
    $started = new DeferredFuture();
    $resume = new DeferredFuture();
    $engine = new TemplateEngine();
    $firstView = $engine->view('phenix-concurrent-test', ['name' => 'first', 'started' => $started, 'resume' => $resume]);
    $secondStarted = new DeferredFuture();
    $secondResume = new DeferredFuture();
    $secondResume->complete();
    $secondView = $engine->view('phenix-concurrent-test', ['name' => 'second', 'started' => $secondStarted, 'resume' => $secondResume]);

    try {
        $first = \Amp\async(fn (): string => $firstView->render());
        $started->getFuture()->await();
        $second = \Amp\async(fn (): string => $secondView->render());
        \Amp\delay(0);
        expect($secondStarted->isComplete())->toBeFalse();
        $resume->complete();
        $firstOutput = $first->await();
        $secondOutput = $second->await();
        expect($firstOutput)->toContain('first-start', 'first-end')->not->toContain('second');
        expect($secondOutput)->toContain('second-start', 'second-end')->not->toContain('first');
    } finally {
        if (! $resume->isComplete()) {
            $resume->complete();
        }
        unlink($source);
        View::clearCache();
    }
});

it('rejects a view symlink pointing outside its source directory', function (): void {
    $link = (new ViewsConfig())->path('phenix-outside-test');
    $target = tempnam(sys_get_temp_dir(), 'phenix-outside-');
    file_put_contents($target, 'outside');
    symlink($target, $link);

    try {
        expect(fn () => (new TemplateEngine())->view('phenix-outside-test'))
            ->toThrow(ViewNotFoundException::class);
    } finally {
        unlink($link);
        unlink($target);
    }
});

it('removes temporary cache files if publishing fails', function (): void {
    $contract = FileContract::class;
    $original = App::make($contract);
    $engine = new TemplateEngine();
    $config = new ViewsConfig();
    $engine->clearCache();
    $file = new class () extends File {
        public function move(string $from, string $to): void
        {
            throw new RuntimeException('Unable to publish view');
        }
    };
    $this->app->swap($contract, $file);

    try {
        expect(fn () => $engine->compile('welcome'))
            ->toThrow(RuntimeException::class, 'Unable to publish view');
        expect(glob($config->compiledPath() . 'view-*'))->toBe([]);
        expect(is_file($config->cachePath('welcome')))->toBeFalse();
    } finally {
        $this->app->swap($contract, $original);
    }
});

it('renders the first available include and each item with inherited variables', function (): void {
    $config = new ViewsConfig();
    $source = $config->path('phenix-each-test');
    $partial = $config->path('phenix-each-partial');
    file_put_contents($source, <<<'BLADE'
@includeWhen(false, 'missing-conditional')
@includeFirst(['missing-first', 'phenix-each-partial'], ['user' => 'first', 'key' => 0])
@each('phenix-each-partial', $users, 'user')
BLADE);
    file_put_contents($partial, '{{ $prefix }}:{{ $key }}:{{ $user }};');

    try {
        $output = (new TemplateEngine())->view('phenix-each-test', [
            'prefix' => 'item', 'users' => ['alice', 'bob'],
        ])->render();
        expect($output)->toContain('item:0:first;', 'item:0:alice;', 'item:1:bob;');
    } finally {
        unlink($source);
        unlink($partial);
        View::clearCache();
    }
});

it('keeps compiler state independent when custom directives suspend', function (): void {
    $config = new ViewsConfig();
    $firstPath = $config->path('phenix-compiler-first');
    $secondPath = $config->path('phenix-compiler-second');
    file_put_contents($firstPath, '@forelse ($items as $item)@pause{{ $item }}@empty empty-first @endforelse');
    file_put_contents($secondPath, '@forelse ($items as $item){{ $item }}@empty empty-second @endforelse');
    $started = new DeferredFuture();
    $resume = new DeferredFuture();
    $engine = new TemplateEngine();
    $engine->directive('pause', function () use ($started, $resume): string {
        $started->complete();
        $resume->getFuture()->await();

        return '';
    });

    try {
        $first = \Amp\async(fn () => $engine->view('phenix-compiler-first', ['items' => []]));
        $started->getFuture()->await();
        $second = $engine->view('phenix-compiler-second', ['items' => []]);
        $resume->complete();
        expect($first->await()->render())->toContain('empty-first');
        expect($second->render())->toContain('empty-second');
    } finally {
        if (! $resume->isComplete()) {
            $resume->complete();
        }
        unlink($firstPath);
        unlink($secondPath);
        View::clearCache();
    }
});

it('prefers application overrides for package views', function (): void {
    $config = new ViewsConfig();
    $name = 'package' . bin2hex(random_bytes(4));
    $package = base_path('storage/framework/testing/' . $name);
    $vendor = $config->path() . 'vendor';
    $override = $vendor . DIRECTORY_SEPARATOR . $name;
    $createdVendor = ! is_dir($vendor);
    mkdir($package, 0755, true);
    mkdir($override, 0755, true);
    file_put_contents($package . DIRECTORY_SEPARATOR . 'index.php', 'package');
    file_put_contents($override . DIRECTORY_SEPARATOR . 'index.php', 'override');

    try {
        App::make(PackageRegistry::class)->addViewPaths($package, $name);
        expect((new TemplateEngine())->view("{$name}::index")->render())->toContain('override');
    } finally {
        unlink($package . DIRECTORY_SEPARATOR . 'index.php');
        unlink($override . DIRECTORY_SEPARATOR . 'index.php');
        rmdir($package);
        rmdir($override);
        if ($createdVendor) {
            rmdir($vendor);
        }
        View::clearCache();
    }
});

it('supports section controls and block PHP directives', function (): void {
    $source = (new ViewsConfig())->path('phenix-section-test');
    file_put_contents($source, <<<'BLADE'
@section('content')first@stop
@section('content')second@overwrite
@yield('content') @yield('missing', 'fallback')
@hasSection('content')present@endif
@php
    $message = 'block';
@endphp
{{ $message }}
@unset($message)
@empty($message)removed@endempty
BLADE);

    try {
        $output = (new TemplateEngine())->view('phenix-section-test')->render();
        expect($output)->toContain('second', 'fallback', 'present', 'block', 'removed')->not->toContain('first');
    } finally {
        unlink($source);
        View::clearCache();
    }
});

it('allows other fibers to run while compilation waits for filesystem operations', function (string $operation): void {
    $engine = new TemplateEngine();
    $engine->clearCache();
    $original = App::make(FileContract::class);
    $started = new DeferredFuture();
    $resume = new DeferredFuture();
    $file = new class ($operation, $started, $resume) extends File {
        public function __construct(private string $operation, private DeferredFuture $started, private DeferredFuture $resume)
        {
            parent::__construct();
        }

        private function pause(string $operation): void
        {
            if ($this->operation === $operation && ! $this->started->isComplete()) {
                $this->started->complete();
                $this->resume->getFuture()->await();
            }
        }

        public function realPath(string $path): string|false
        {
            $this->pause('realPath');

            return parent::realPath($path);
        }

        public function getStatus(string $path): array|null
        {
            $this->pause('getStatus');

            return parent::getStatus($path);
        }

        public function get(string $path): string
        {
            $this->pause('get');

            return parent::get($path);
        }

        public function openFile(string $path, string $mode = 'w'): AmpFile
        {
            $this->pause('openFile');

            $file = parent::openFile($path, $mode);

            if ($this->operation !== 'write') {
                return $file;
            }

            $wrapped = Mockery::mock(AmpFile::class);
            $wrapped->shouldReceive('write')->once()->andReturnUsing(function (string $content) use ($file): void {
                $this->pause('write');
                $file->write($content);
            });
            $wrapped->shouldReceive('close')->once()->andReturnUsing(fn () => $file->close());

            return $wrapped;
        }

        public function move(string $from, string $to): void
        {
            $this->pause('move');
            parent::move($from, $to);
        }
    };
    $this->app->swap(FileContract::class, $file);

    try {
        $compile = \Amp\async(fn () => $engine->compile('welcome'));
        $started->getFuture()->await();
        expect($compile->isComplete())->toBeFalse();
        $observer = \Amp\async(fn (): string => 'running');
        expect($observer->await())->toBe('running');
        $resume->complete();
        $compile->await();
    } finally {
        if (! $resume->isComplete()) {
            $resume->complete();
        }
        $this->app->swap(FileContract::class, $original);
        $engine->clearCache();
    }
})->with(['realPath', 'getStatus', 'get', 'openFile', 'write', 'move']);

<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Facades\Config;
use Phenix\Facades\View;
use Phenix\Packages\PackageRegistry;
use Phenix\Views\Exceptions\ViewNotFoundException;
use Phenix\Views\TemplateEngine;
use Phenix\Views\ViewsConfig;

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

it('delegates view compilation before rendering', function (): void {
    $engine = new class () extends TemplateEngine {
        public array $compiled = [];

        public function compile(string $template): void
        {
            $this->compiled[] = $template;
            parent::compile($template);
        }
    };

    $engine->clearCache();
    $view = $engine->view('welcome', ['title' => 'Welcome', 'colors' => []]);

    expect($engine->compiled)->toBe(['welcome']);
    expect($view->render())->toBe($view->render());
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

it('renders empty and zero inline sections while preserving block html', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    foreach (['', '0'] as $title) {
        $output = $template->view('users.index', ['title' => $title])->render();

        expect($output)->toContain("<title>{$title}</title>", '<h1>User index</h1>');
    }
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

it('renders Blade control flow, stacks and conditional includes', function (): void {
    $path = (new ViewsConfig())->path('blade-features.php');
    file_put_contents($path, <<<'BLADE'
@push('scripts')<script>ready()</script>@endpush
@forelse ($items as $item)
    <span>{{ $item }}</span>
@empty
    <span>Empty</span>
@endforelse
@includeIf('missing-partial')
@stack('scripts')
BLADE);

    try {
        $engine = new TemplateEngine();
        $engine->clearCache();
        $view = $engine->view('blade-features', ['items' => ['<unsafe>']]);
        $output = $view->render();

        expect($output)->toContain('&lt;unsafe&gt;')->not->toContain('<unsafe>');
        expect($output)->toContain('<script>ready()</script>');
        expect($view->render())->toBe($output);
        expect($engine->view('blade-features', ['items' => []])->render())->toContain('Empty');
    } finally {
        unlink($path);
        View::clearCache();
    }
});

it('recompiles changed views and uses the compiled file for precaching', function (): void {
    $config = new ViewsConfig();
    $source = $config->path('blade-cache-test.php');
    $compiled = $config->cachePath('blade-cache-test');

    file_put_contents($source, 'First {{ $name }}');

    try {
        $engine = new TemplateEngine();
        $engine->clearCache();
        $engine->compile('blade-cache-test');

        expect(is_file($compiled))->toBeTrue();
        expect($engine->view('blade-cache-test', ['name' => 'A'])->render())->toContain('First A');

        file_put_contents($source, 'Second {{ $name }}');

        touch($compiled, time() - 5);
        clearstatcache();

        expect($engine->view('blade-cache-test', ['name' => 'B'])->render())->toContain('Second B');

        $engine->clearCache();

        expect(is_file($compiled))->toBeFalse();
    } finally {
        unlink($source);

        View::clearCache();
    }
});

it('resolves namespaced includes and application overrides', function (): void {
    $package = base_path('storage/framework/testing/package-views-' . uniqid());
    $override = (new ViewsConfig())->path() . 'vendor' . DIRECTORY_SEPARATOR . 'blog';

    mkdir($package, 0755, true);
    mkdir($override, 0755, true);

    file_put_contents("{$package}/index.php", "@include('blog::partial')");
    file_put_contents("{$package}/partial.php", 'Package');
    file_put_contents("{$override}/partial.php", 'Override {{ $name }}');

    try {
        App::make(PackageRegistry::class)->addViewPaths($package, 'blog');

        $engine = new TemplateEngine();
        $engine->clearCache();

        expect($engine->view('blog::index', ['name' => 'view'])->render())->toContain('Override view');
    } finally {
        unlink("{$package}/index.php");
        unlink("{$package}/partial.php");
        unlink("{$override}/partial.php");
        rmdir($package);
        rmdir($override);

        View::clearCache();
    }
});

it('isolates views rendered concurrently across fibers', function (): void {
    $path = (new ViewsConfig())->path('blade-concurrent.php');
    file_put_contents($path, <<<'BLADE'
{{ $name }}-start
@php(\Amp\delay(0.01))
{{ $name }}-end
BLADE);

    try {
        $engine = new TemplateEngine();
        $engine->clearCache();
        $first = \Amp\async(fn (): string => $engine->view('blade-concurrent', ['name' => 'first'])->render());
        $second = \Amp\async(fn (): string => $engine->view('blade-concurrent', ['name' => 'second'])->render());

        [$firstOutput, $secondOutput] = \Amp\Future\await([$first, $second]);

        expect($firstOutput)->toContain('first-start', 'first-end')->not->toContain('second');
        expect($secondOutput)->toContain('second-start', 'second-end')->not->toContain('first');
    } finally {
        unlink($path);
        View::clearCache();
    }
});

it('allows other fibers to advance while resolving a view', function (): void {
    $engine = new TemplateEngine();
    $engine->clearCache();
    $render = \Amp\async(fn (): string => $engine->view('welcome', ['title' => 'Async', 'colors' => []])->render());
    $other = \Amp\async(fn (): bool => ! $render->isComplete());

    expect($other->await())->toBeTrue();
    expect($render->await())->toContain('Async');
});

it('rejects views that resolve through symlinks outside their view directory', function (): void {
    $config = new ViewsConfig();
    $link = $config->path('outside-symlink');
    $external = tempnam(sys_get_temp_dir(), 'phenix-view-');

    file_put_contents($external, 'outside');
    symlink($external, $link);

    try {
        $engine = new TemplateEngine();

        expect(fn () => $engine->view('outside-symlink'))->toThrow(ViewNotFoundException::class);
    } finally {
        unlink($link);
        unlink($external);
    }
});

it('accepts view symlinks whose targets stay inside the view directory', function (): void {
    $config = new ViewsConfig();
    $link = $config->path('inside-symlink');
    $target = $config->path('inside-target');

    file_put_contents($target, 'Inside {{ $name }}');
    symlink($target, $link);

    try {
        $engine = new TemplateEngine();
        $engine->clearCache();

        expect($engine->view('inside-symlink', ['name' => 'view'])->render())->toContain('Inside view');
    } finally {
        unlink($link);
        unlink($target);

        View::clearCache();
    }
});

it('publishes concurrent compilations without leaving temporary files', function (): void {
    $engine = new TemplateEngine();
    $config = new ViewsConfig();
    $engine->clearCache();

    $first = \Amp\async(fn () => $engine->compile('welcome'));
    $second = \Amp\async(fn () => $engine->compile('welcome'));

    \Amp\Future\await([$first, $second]);

    expect($engine->view('welcome', ['title' => 'Concurrent', 'colors' => []])->render())->toContain('Concurrent');
    expect(glob($config->compiledPath() . 'view-*'))->toBe([]);
});

it('reports cache write failures', function (): void {
    $compiledPath = Config::get('view.compiled_path');
    $missing = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phenix-missing-' . bin2hex(random_bytes(8));

    Config::set('view.compiled_path', $missing);

    try {
        $engine = new TemplateEngine();

        expect(fn () => $engine->compile('welcome'))->toThrow(\Phenix\Exceptions\RuntimeException::class);
    } finally {
        Config::set('view.compiled_path', $compiledPath);
    }
});

it('cleans temporary files after a failed or cancelled cache publication', function (bool $cancelled): void {
    $contract = \Phenix\Filesystem\Contracts\File::class;
    $original = App::make($contract);
    $engine = new TemplateEngine();
    $config = new ViewsConfig();
    $engine->clearCache();

    $file = new class ($cancelled) extends \Phenix\Filesystem\File {
        public function __construct(private bool $cancelled)
        {
            parent::__construct();
        }

        public function move(string $from, string $to): void
        {
            if ($this->cancelled) {
                throw new \Amp\CancelledException();
            }

            throw new \Phenix\Exceptions\RuntimeException('Failed to publish cache');
        }
    };

    $this->app->swap($contract, $file);

    try {
        $exception = $cancelled ? \Amp\CancelledException::class : \Phenix\Exceptions\RuntimeException::class;

        expect(fn () => $engine->compile('welcome'))->toThrow($exception);
        expect(glob($config->compiledPath() . 'view-*'))->toBe([]);
    } finally {
        $this->app->swap($contract, $original);
    }
})->with([false, true]);

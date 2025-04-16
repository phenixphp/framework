<?php

declare(strict_types=1);

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Facades\File;
use Phenix\Facades\View;
use Phenix\Views\Config;
use Phenix\Views\Tasks\CompileTemplates;
use Symfony\Component\Console\Tester\CommandTester;

it('compile all available views', function (): void {
    $config = new Config();

    /** @var CommandTester $command */
    $command = $this->phenix('view:cache');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('All views were compiled successfully!.');

    $templates = array_filter(
        File::listFiles($config->compiledPath()),
        fn (string $filename): bool => str_ends_with($filename, '.php')
    );

    expect($templates)->toHaveCount(8);
});

it('run parallel task', function (): void {
    View::clearCache();

    $channel = new class () implements Channel {
        public function receive(?Cancellation $cancellation = null): mixed
        {
            return true;
        }

        public function send(mixed $data): void
        {
            //
        }

        public function close(): void
        {
            //
        }

        public function isClosed(): bool
        {
            return true;
        }

        public function onClose(Closure $onClose): void
        {
            //
        }
    };

    $cancellation = new class () implements Cancellation {
        public function subscribe(\Closure $callback): string
        {
            return 'id';
        }

        public function unsubscribe(string $id): void
        {

        }

        public function isRequested(): bool
        {
            return true;
        }

        public function throwIfRequested(): void
        {
            //
        }
    };

    $task = new CompileTemplates(['app']);

    expect($task->run($channel, $cancellation))->toBeTruthy();
});

<?php

declare(strict_types=1);

use Phenix\Facades\File;
use Phenix\Facades\View;
use Phenix\Tasks\Result;
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
    $task = new CompileTemplates(['app']);

    /** @var Result $result */
    $result = $task->run($this->getFakeChannel(), $this->getFakeCancellation());

    expect($result->isSuccess())->toBeTruthy();
});

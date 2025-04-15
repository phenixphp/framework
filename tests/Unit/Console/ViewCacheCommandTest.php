<?php

declare(strict_types=1);

use Phenix\Facades\File;
use Phenix\Views\Config;
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

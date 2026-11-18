<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Packages\PackageRegistry;
use Symfony\Component\Console\Tester\CommandTester;

it('publishes tagged package files', function (): void {
    $source = base_path('storage/framework/testing/package-source-' . uniqid() . '.php');
    $destination = base_path('storage/framework/testing/package-destination-' . uniqid() . '.php');
    file_put_contents($source, '<?php return [];');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('TestProvider', [$source => $destination], 'test-config');

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--tag' => 'test-config']);

    $command->assertCommandIsSuccessful();

    expect($destination)->toBeFile()
        ->and(file_get_contents($destination))->toBe('<?php return [];');

    unlink($source);
    unlink($destination);
});

it('requires a publication selector', function (): void {
    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish');

    expect($command->getStatusCode())->toBe(1)
        ->and($command->getDisplay())->toContain('Specify --provider, --tag, or --all.');
});

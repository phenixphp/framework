<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Packages\PackageManifest;
use Symfony\Component\Console\Tester\CommandTester;

it('rebuilds the package manifest', function (): void {
    /** @var CommandTester $command */
    $command = $this->phenix('package:discover');

    $command->assertCommandIsSuccessful();

    /** @var PackageManifest $manifest */
    $manifest = App::make(PackageManifest::class);

    expect($command->getDisplay())->toContain('Discovered packages:')
        ->and($manifest->path())->toBeFile();
});

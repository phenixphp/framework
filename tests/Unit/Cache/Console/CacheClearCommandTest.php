<?php

declare(strict_types=1);

use Symfony\Component\Console\Tester\CommandTester;

it('clears the cache', function (): void {
    /** @var CommandTester $command */
    $command = $this->phenix('cache:clear');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Cached data cleared successfully!');
});

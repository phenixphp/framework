<?php

declare(strict_types=1);

use Symfony\Component\Console\Tester\CommandTester;

it('clear compiled views successfully', function () {
    /** @var CommandTester $command */
    $command = $this->phenix('view:clear');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Compiled views cleared successfully!.');
});

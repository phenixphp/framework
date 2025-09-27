<?php

declare(strict_types=1);

use Phenix\App;

it('check if app is in local environment', function (): void {
    expect(App::isLocal())->toBeTrue();
    expect(App::isProduction())->toBeFalse();
});

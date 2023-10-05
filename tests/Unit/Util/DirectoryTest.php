<?php

declare(strict_types=1);

use Phenix\Util\Directory;

it('can list nested directories', function () {
    $directories = Directory::all(dirname(__FILE__, 3) . '/fixtures/application');

    expect($directories)->toBeArray();
    expect($directories)->toBeGreaterThan(3);
});

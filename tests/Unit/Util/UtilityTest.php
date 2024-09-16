<?php

declare(strict_types=1);

use Phenix\Util\Utility;

it('prevents create class instances', function () {
    new Utility();
})->throws(Error::class);

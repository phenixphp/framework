<?php

declare(strict_types=1);

use Phenix\Util\Str;

it('prevents create class instances', function () {
    new Str();
})->throws(Error::class);

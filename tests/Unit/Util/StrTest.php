<?php

declare(strict_types=1);

use Phenix\Util\Str;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\UuidV4;

it('creates universal identifiers', function () {
    $uuid = Str::uuid();
    $ulid = Str::ulid();

    expect($uuid)->toBeInstanceOf(UuidV4::class);
    expect($ulid)->toBeInstanceOf(Ulid::class);
});

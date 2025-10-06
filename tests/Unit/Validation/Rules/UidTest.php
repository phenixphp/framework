<?php

declare(strict_types=1);

use Phenix\Validation\Rules\Ulid;
use Phenix\Validation\Rules\Uuid;

it('uuid ulid messages', function () {
    $uuid = (new Uuid())->setField('id')->setData(['id' => 'not-uuid']);

    assertFalse($uuid->passes());
    assertStringContainsString('valid UUID', (string) $uuid->message());

    $ulid = (new Ulid())->setField('id')->setData(['id' => 'not-ulid']);

    assertFalse($ulid->passes());
    assertStringContainsString('valid ULID', (string) $ulid->message());
});

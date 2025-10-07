<?php

declare(strict_types=1);

use Phenix\Validation\Rules\IsFile;

it('fails is_file when value not instance of BufferedFile', function () {
    $rule = new IsFile();
    $rule->setField('upload')->setData(['upload' => 'string']);

    assertFalse($rule->passes());
    assertStringContainsString('must be a file', (string) $rule->message());
});

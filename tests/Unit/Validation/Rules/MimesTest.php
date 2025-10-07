<?php

declare(strict_types=1);

use Amp\Http\Server\FormParser\BufferedFile;
use Phenix\Validation\Rules\Mimes;

it('passes when file mime type is allowed', function (): void {
    $file = __DIR__ . '/../../../fixtures/files/user.png';
    $fileInfo = new SplFileInfo($file);
    $contentType = mime_content_type($file);

    $bufferedFile = new BufferedFile(
        $fileInfo->getFilename(),
        file_get_contents($file),
        $contentType,
        [['Content-Type', $contentType]]
    );

    $rule = new Mimes(['image/png']);
    $rule->setField('image')->setData(['image' => $bufferedFile]);

    assertTrue($rule->passes());
});

it('fails when file mime type is not allowed', function (): void {
    $file = __DIR__ . '/../../../fixtures/files/user.png';
    $fileInfo = new SplFileInfo($file);
    $contentType = mime_content_type($file); // image/png

    $bufferedFile = new BufferedFile(
        $fileInfo->getFilename(),
        file_get_contents($file),
        $contentType,
        [['Content-Type', $contentType]]
    );

    $rule = new Mimes(['image/jpeg']);
    $rule->setField('image')->setData(['image' => $bufferedFile]);

    assertFalse($rule->passes());
    assertStringContainsString('image/jpeg', (string) $rule->message());
});

it('passes when file mime type is in multi-value whitelist', function (): void {
    $file = __DIR__ . '/../../../fixtures/files/user.png';
    $fileInfo = new SplFileInfo($file);
    $contentType = mime_content_type($file); // image/png

    $bufferedFile = new BufferedFile(
        $fileInfo->getFilename(),
        file_get_contents($file),
        $contentType,
        [['Content-Type', $contentType]]
    );

    $rule = new Mimes(['image/jpeg', 'image/png']);
    $rule->setField('image')->setData(['image' => $bufferedFile]);

    assertTrue($rule->passes());
});

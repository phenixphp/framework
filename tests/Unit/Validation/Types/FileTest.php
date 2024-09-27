<?php

declare(strict_types=1);

use Amp\Http\Server\FormParser\BufferedFile;
use Phenix\Validation\Rules\IsFile;
use Phenix\Validation\Types\File;

it('runs validation for valid files', function () {
    $rules = File::required()->toArray();
    $file = __DIR__ . '/../../../fixtures/files/user.png';

    $fileInfo = new SplFileInfo($file);

    $contentType = mime_content_type($file);

    $bufferedFile = new BufferedFile(
        $fileInfo->getFilename(),
        file_get_contents($file),
        $contentType,
        [['Content-Type', $contentType]]
    );

    foreach ($rules['type'] as $rule) {
        $rule->setField('image');
        $rule->setData(['image' => $bufferedFile]);

        expect($rule->passes())->toBeTruthy();
    }
});

it('runs validation for invalid files', function () {
    $rules = File::required()->toArray();

    foreach ($rules['type'] as $rule) {
        $rule->setField('image');
        $rule->setData(['image' => 'value']);

        if ($rule instanceof IsFile) {
            expect($rule->passes())->toBeFalsy();
        } else {
            expect($rule->passes())->toBeTruthy();
        }
    }
});

it('runs validation for file sizes', function (string $method, float|int $value) {
    $rules = File::required()->{$method}($value)->toArray();
    $file = __DIR__ . '/../../../fixtures/files/user.png';

    $fileInfo = new SplFileInfo($file);

    $contentType = mime_content_type($file);

    $bufferedFile = new BufferedFile(
        $fileInfo->getFilename(),
        file_get_contents($file),
        $contentType,
        [['Content-Type', $contentType]]
    );

    foreach ($rules['type'] as $rule) {
        $rule->setField('image');
        $rule->setData(['image' => $bufferedFile]);

        expect($rule->passes())->toBeTruthy();
    }
})->with([
    'minimum size' => ['min', 1],
    'maximum size' => ['max', 2],
    'size' => ['size', 1.306],
]);

it('runs validation for valid mime type', function () {
    $rules = File::required()->mimes(['image/png'])->toArray();
    $file = __DIR__ . '/../../../fixtures/files/user.png';

    $fileInfo = new SplFileInfo($file);

    $contentType = mime_content_type($file);

    $bufferedFile = new BufferedFile(
        $fileInfo->getFilename(),
        file_get_contents($file),
        $contentType,
        [['Content-Type', $contentType]]
    );

    foreach ($rules['type'] as $rule) {
        $rule->setField('image');
        $rule->setData(['image' => $bufferedFile]);

        expect($rule->passes())->toBeTruthy();
    }
});

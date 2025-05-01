<?php

declare(strict_types=1);

use Phenix\Crypto\Bin2Base64;
use Phenix\Crypto\Constants\BinBase64Mode;

it('encode and decode successfully', function (): void {
    $data = random_bytes(16);
    $encoded = Bin2Base64::encode($data);
    $decoded = Bin2Base64::decode($encoded);

    expect($decoded)->toBeString()->and($decoded)->toEqual($data);
})->group('crypto');

it('encode and decode with no padding successfully', function (): void {
    $data = random_bytes(16);
    $encoded = Bin2Base64::encode($data, BinBase64Mode::BASE_64_NO_PADDING);
    $decoded = Bin2Base64::decode($encoded);

    expect($decoded)->toBeString()->and($decoded)->toEqual($data);
})->group('crypto');

it('encode and decode with URL variant successfully', function (): void {
    $data = random_bytes(16);
    $encoded = Bin2Base64::encode($data, BinBase64Mode::BASE_64_URL);
    $decoded = Bin2Base64::decode($encoded);

    expect($decoded)->toBeString()->and($decoded)->toEqual($data);
})->group('crypto');

it('encode and decode with URL variant and no padding successfully', function (): void {
    $data = random_bytes(16);
    $encoded = Bin2Base64::encode($data, BinBase64Mode::BASE_64_URL_NO_PADDING);
    $decoded = Bin2Base64::decode($encoded);

    expect($decoded)->toBeString()->and($decoded)->toEqual($data);
})->group('crypto');

it('decode string without prefix successfully', function (): void {
    $data = random_bytes(16);
    $encoded = Bin2Base64::encode($data);
    $decoded = Bin2Base64::decode(substr($encoded, 7));

    expect($decoded)->toBeString()->and($decoded)->toEqual($data);
})->group('crypto');

it('throws exception for invalid prefix', function (): void {
    $data = random_bytes(16);
    $encoded = Bin2Base64::encode($data);
    Bin2Base64::decode('invalid_prefix:' . substr($encoded, 7));
})->throws(InvalidArgumentException::class)
->group('crypto');

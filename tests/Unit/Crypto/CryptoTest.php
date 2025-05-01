<?php

declare(strict_types=1);

use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Crypto\Exceptions\DecryptException;
use Phenix\Crypto\Exceptions\EncryptException;

it('generate encoded key successfully', function (): void {
    $key = Crypto::generateEncodedKey();

    expect($key)->toBeString();
})->group('crypto');

it('encrypt and decrypt successfully', function (): void {
    $key = Crypto::generateEncodedKey();

    Config::set('app.key', $key);

    $data = ['foo' => 'bar'];
    $encrypted = Crypto::encrypt($data, true);
    $decrypted = Crypto::decrypt($encrypted, true);

    expect($decrypted)->toBeArray()->and($decrypted)->toEqual($data);
})->group('crypto');

it('encrypt and decrypt string successfully', function (): void {
    $key = Crypto::generateEncodedKey();

    Config::set('app.key', $key);

    $data = 'foo bar';
    $encrypted = Crypto::encryptString($data);
    $decrypted = Crypto::decryptString($encrypted);

    expect($decrypted)->toBeString()->and($decrypted)->toEqual($data);
})->group('crypto');

it('throws exception on failed encryption', function (): void {
    $key = Crypto::generateEncodedKey();

    $key = substr($key, 7);

    Config::set('app.key', $key);

    $data = ['foo' => 'bar'];

    Crypto::encrypt($data, true);
})->throws(EncryptException::class)
->group('crypto');

it('throws exception on failed decryption', function (): void {
    $key = Crypto::generateEncodedKey();

    Config::set('app.key', $key);

    Crypto::decryptString('invalid-encrypted-string');
})->throws(DecryptException::class)
->group('crypto');

<?php

declare(strict_types=1);

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Crypto\Cipher;
use Phenix\Crypto\Exceptions\DecryptException;
use Phenix\Crypto\Exceptions\EncryptException;
use Phenix\Crypto\Exceptions\MissingKeyException;
use Phenix\Crypto\Tasks\Decrypt;
use Phenix\Crypto\Tasks\Encrypt;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;
use Phenix\Facades\Hash;
use Phenix\Tasks\Result;

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

it('encrypt and decrypt using cipher', function (): void {
    $key = Crypto::generateEncodedKey();

    $data = ['foo' => 'bar'];

    $cipher = new Cipher($key);
    $encrypted = $cipher->encrypt($data, true);
    $decrypted = $cipher->decrypt($encrypted, true);

    expect($decrypted)->toBeArray()->and($decrypted)->toEqual($data);
})->group('crypto');

it('throws exception when key is missing', function (): void {
    $data = ['foo' => 'bar'];

    Crypto::encrypt($data, true);
})->throws(MissingKeyException::class)
->group('crypto');

it('run encryption and decryption tasks successfully', function (): void {
    $channel = new class () implements Channel {
        public function receive(Cancellation|null $cancellation = null): mixed
        {
            return true;
        }

        public function send(mixed $data): void
        {
            //
        }

        public function close(): void
        {
            //
        }

        public function isClosed(): bool
        {
            return false;
        }

        public function onClose(Closure $onClose): void
        {
            //
        }
    };

    $cancellation = new class () implements Cancellation {
        public function subscribe(Closure $callback): string
        {
            return 'id';
        }

        public function unsubscribe(string $id): void
        {

        }

        public function isRequested(): bool
        {
            return true;
        }

        public function throwIfRequested(): void
        {
            //
        }
    };

    $key = Crypto::generateEncodedKey();

    $data = ['foo' => 'bar'];

    $task = new Encrypt($key, $data);

    $result = $task->run($channel, $cancellation);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->isSuccess())->toBeTrue();
    expect($result->isFailure())->toBeFalse();
    expect($result->output())->toBeString();
    expect($result->message())->toBeNull();

    $encrypted = $result->output();

    $task = new Decrypt($key, $encrypted);

    $result = $task->run($channel, $cancellation);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->isSuccess())->toBeTrue();
    expect($result->isFailure())->toBeFalse();
    expect($result->output())->toBeArray();
    expect($result->message())->toBeNull();
    expect($result->output())->toEqual($data);
})->group('crypto');

it('run encryption with failed result', function (): void {
    $channel = new class () implements Channel {
        public function receive(Cancellation|null $cancellation = null): mixed
        {
            return true;
        }

        public function send(mixed $data): void
        {
            //
        }

        public function close(): void
        {
            //
        }

        public function isClosed(): bool
        {
            return false;
        }

        public function onClose(Closure $onClose): void
        {
            //
        }
    };

    $cancellation = new class () implements Cancellation {
        public function subscribe(Closure $callback): string
        {
            return 'id';
        }

        public function unsubscribe(string $id): void
        {
            //
        }

        public function isRequested(): bool
        {
            return true;
        }

        public function throwIfRequested(): void
        {
            //
        }
    };

    $key = Crypto::generateEncodedKey();

    $key = substr($key, 7);

    $data = ['foo' => 'bar'];

    $task = new Encrypt($key, $data);

    $result = $task->run($channel, $cancellation);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->isSuccess())->toBeFalse();
    expect($result->isFailure())->toBeTrue();
    expect($result->output())->toBeNull();
})
->group('crypto');

it('run decryption with failed result', function (): void {
    $channel = new class () implements Channel {
        public function receive(Cancellation|null $cancellation = null): mixed
        {
            return true;
        }

        public function send(mixed $data): void
        {
            //
        }

        public function close(): void
        {
            //
        }

        public function isClosed(): bool
        {
            return false;
        }

        public function onClose(Closure $onClose): void
        {
            //
        }
    };

    $cancellation = new class () implements Cancellation {
        public function subscribe(Closure $callback): string
        {
            return 'id';
        }

        public function unsubscribe(string $id): void
        {
            //
        }

        public function isRequested(): bool
        {
            return true;
        }

        public function throwIfRequested(): void
        {
            //
        }
    };

    $key = Crypto::generateEncodedKey();

    $task = new Decrypt($key, 'invalid-encrypted-string');

    $result = $task->run($channel, $cancellation);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->isSuccess())->toBeFalse();
    expect($result->isFailure())->toBeTrue();
    expect($result->output())->toBeNull();
})
->group('crypto');

it('execute hashing operations successfully', function (): void {
    $password = 'password';

    $hash = Hash::make($password);
    $isValid = Hash::verify($hash, $password);
    $needsRehash = Hash::needsRehash($hash);

    expect($hash)->toBeString()
        ->and($isValid)->toBeTrue()
        ->and($needsRehash)->toBeFalse();
})->group('crypto');

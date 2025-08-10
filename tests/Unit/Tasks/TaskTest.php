<?php

declare(strict_types=1);

use Phenix\Tasks\Exceptions\BootstrapAppException;
use Phenix\Tasks\Task;
use Tests\Unit\Tasks\Internal\BasicTask;
use Tests\Unit\Tasks\Internal\DelayableTask;

it('get task output', function (): void {
    $task = new BasicTask();

    $result = $task->output();

    expect($result->isSuccess())->toBeTrue();
    expect($result->output())->toBe('Task completed successfully');
});

it('handle task cancellation by timeout', function (): void {
    $task = new DelayableTask(delay: 4);
    $task->setTimeout(1); // Set a short timeout

    $result = $task->output();
    dump($result);
    expect($result->isFailure())->toBeTrue();
    expect($result->message())->toBe('The operation was cancelled');
});

it('sets PHENIX_BASE_PATH in env and $_ENV when booting settings are applied', function (): void {
    $originalEnv = getenv('PHENIX_BASE_PATH');
    $originalServerEnv = $_ENV['PHENIX_BASE_PATH'] ?? null;

    putenv('PHENIX_BASE_PATH');
    unset($_ENV['PHENIX_BASE_PATH']);

    Task::setBootingSettings();

    $expected = base_path();
    expect(getenv('PHENIX_BASE_PATH'))->toBe($expected);
    expect($_ENV['PHENIX_BASE_PATH'])->toBe($expected);

    if ($originalEnv === false) {
        putenv('PHENIX_BASE_PATH');
    } else {
        putenv('PHENIX_BASE_PATH=' . $originalEnv);
    }

    if ($originalServerEnv === null) {
        unset($_ENV['PHENIX_BASE_PATH']);
    } else {
        $_ENV['PHENIX_BASE_PATH'] = $originalServerEnv;
    }
});

it('throw exception if PHENIX_BASE_PATH is not set', function (): void {
    $originalEnv = getenv('PHENIX_BASE_PATH');
    $originalServerEnv = $_ENV['PHENIX_BASE_PATH'] ?? null;

    putenv('PHENIX_BASE_PATH');
    unset($_ENV['PHENIX_BASE_PATH']);

    expect(function (): void {
        $task = new BasicTask();
        $task->run($this->getFakeChannel(), $this->getFakeCancellation());
    })->toThrow(BootstrapAppException::class);

    if ($originalEnv === false) {
        putenv('PHENIX_BASE_PATH');
    } else {
        putenv('PHENIX_BASE_PATH=' . $originalEnv);
    }

    if ($originalServerEnv === null) {
        unset($_ENV['PHENIX_BASE_PATH']);
    } else {
        $_ENV['PHENIX_BASE_PATH'] = $originalServerEnv;
    }
});

<?php

declare(strict_types=1);

use Phenix\Tasks\Worker;
use Tests\Unit\Tasks\Internal\BasicTask;

it('run task from stand alone worker', function (): void {
    $task = new BasicTask();

    $worker = new Worker();

    [$result] = $worker->push($task)->run();

    expect($result->isSuccess())->toBeTrue();
    expect($result->output())->toBe('Task completed successfully');
});

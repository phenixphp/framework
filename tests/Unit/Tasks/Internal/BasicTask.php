<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks\Internal;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Tasks\Result;
use Phenix\Tasks\Task;

class BasicTask extends Task
{
    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $output = 'Task completed successfully';
        $message = 'This is a test message';

        return Result::success($output, $message);
    }
}

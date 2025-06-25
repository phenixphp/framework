<?php

declare(strict_types=1);

namespace Tests\Unit\Queue\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Tasks\Result;
use Phenix\Tasks\QueuableTask;

class DummyTask extends QueuableTask
{

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        // Simulate some processing
        $output = 'Task completed successfully';
        $message = 'This is a test message';

        // Return a successful result
        return Result::success($output, $message);
    }
}

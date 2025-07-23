<?php

declare(strict_types=1);

namespace Tests\Unit\Queue\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Result;

class SampleQueuableTask extends QueuableTask
{
    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $output = 'Task completed successfully';
        $message = 'This is a test message';

        return Result::success($output, $message);
    }
}

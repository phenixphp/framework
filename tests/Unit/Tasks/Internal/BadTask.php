<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks\Internal;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Result;

class BadTask extends QueuableTask
{
    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        $message = 'Task failed due to an error';

        return Result::failure(message: $message);
    }
}

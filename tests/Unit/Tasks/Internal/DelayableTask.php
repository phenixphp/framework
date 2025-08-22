<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks\Internal;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Tasks\QueuableTask;
use Phenix\Tasks\Result;

use function Amp\delay;

class DelayableTask extends QueuableTask
{
    public function __construct(protected int $delay = 3)
    {
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        delay($this->delay, cancellation: $cancellation);

        $output = 'Task completed successfully';
        $message = 'This is a test message';

        return Result::success($output, $message);
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

class FetchTask implements Task
{
    public function __construct(
        private readonly string $url,
    ) {
    }

    public function run(Channel $channel, Cancellation $cancellation): string
    {
        dump('Process ID: ' . getmypid());

        return file_get_contents($this->url); // Example blocking function
    }
}

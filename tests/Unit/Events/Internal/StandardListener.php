<?php

declare(strict_types=1);

namespace Tests\Unit\Events\Internal;

use Phenix\Events\AbstractListener;
use Phenix\Events\Contracts\Event;

class StandardListener extends AbstractListener
{
    public function handle(Event $event): mixed
    {
        return 'Event name: ' . $event->getName();
    }
}

<?php

declare(strict_types=1);

use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Facades\Event;

Event::on('user.registered', function (EventContract $event): void {
    // Handle the user registered event
});

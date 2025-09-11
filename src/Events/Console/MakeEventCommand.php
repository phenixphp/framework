<?php

declare(strict_types=1);

namespace Phenix\Events\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:event',
    description: 'Create a new event class'
)]
class MakeEventCommand extends Maker
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the event');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Events';
    }

    protected function commonName(): string
    {
        return 'Event';
    }

    protected function stub(): string
    {
        return 'event.stub';
    }
}

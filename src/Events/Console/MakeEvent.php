<?php

declare(strict_types=1);

namespace Phenix\Events\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:event',
    description: 'Create a new event class'
)]
class MakeEvent extends Maker
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the event');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create event');
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

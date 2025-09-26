<?php

declare(strict_types=1);

namespace Phenix\Events\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:listener',
    description: 'Create a new event listener class'
)]
class MakeListener extends Maker
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the listener');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create listener');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Listeners';
    }

    protected function commonName(): string
    {
        return 'Event Listener';
    }

    protected function stub(): string
    {
        return 'listener.stub';
    }
}

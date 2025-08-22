<?php

declare(strict_types=1);

namespace Phenix\Tasks\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeTask extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:task';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new task.';

    protected string $fileName;

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new task.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The task name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create task');
        $this->addOption('queue', null, InputOption::VALUE_NONE, 'Indicates if task allows to be queued');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Tasks';
    }

    protected function stub(): string
    {
        return $this->input->getOption('queue') ? 'task.queue.stub' : 'task.stub';
    }

    protected function commonName(): string
    {
        return 'Task';
    }
}

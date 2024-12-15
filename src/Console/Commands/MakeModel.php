<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeModel extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:model';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new model.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new model.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The model name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create model');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Models';
    }

    protected function stub(): string
    {
        return 'model.stub';
    }

    protected function commonName(): string
    {
        return 'Model';
    }
}

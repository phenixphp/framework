<?php

declare(strict_types=1);

namespace Phenix\Validation\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeType extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:type';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new validation type.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new validation type.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The type class name');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create type');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Validation' . DIRECTORY_SEPARATOR . 'Types';
    }

    protected function stub(): string
    {
        return 'type.stub';
    }

    protected function commonName(): string
    {
        return 'Type';
    }
}

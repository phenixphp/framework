<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeCollection extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:collection';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new collection.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new collection.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The collection name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create collections');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Collections';
    }

    protected function stub(): string
    {
        return 'collection.stub';
    }

    protected function commonName(): string
    {
        return 'Collection';
    }
}

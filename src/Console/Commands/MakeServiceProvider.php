<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeServiceProvider extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:provider';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new service provider.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new service provider.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The service provider name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create service provider');
    }

    protected function outputDirectory(): string
    {
        return 'app'. DIRECTORY_SEPARATOR . 'Providers';
    }

    protected function stub(): string
    {
        return 'provider.stub';
    }

    protected function commonName(): string
    {
        return 'Service provider';
    }
}

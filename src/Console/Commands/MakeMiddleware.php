<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeMiddleware extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:middleware';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new middleware.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new middleware.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The middleware name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create middleware');
    }

    protected function outputDirectory(): string
    {
        return 'app'. DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Middleware';
    }

    protected function stub(): string
    {
        return 'middleware.stub';
    }

    protected function commonName(): string
    {
        return 'Middleware';
    }
}

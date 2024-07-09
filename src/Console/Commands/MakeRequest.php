<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeRequest extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:request';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new form request.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new form request.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The form request name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create form request');
    }

    protected function outputDirectory(): string
    {
        return 'app'. DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests';
    }

    protected function stub(): string
    {
        return 'request.stub';
    }

    protected function commonName(): string
    {
        return 'Request';
    }
}

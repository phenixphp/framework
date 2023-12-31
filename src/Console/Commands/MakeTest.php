<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeTest extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:test';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new test.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new test class.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The test name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create test');
        $this->addOption('unit', 'u', InputOption::VALUE_NONE, 'Create unit testing');
    }

    protected function outputDirectory(): string
    {
        $base = 'tests' . DIRECTORY_SEPARATOR;

        if ($this->input->getOption('unit')) {
            return $base . 'Unit';
        }

        return $base . 'Feature';
    }

    protected function stub(): string
    {
        return 'test.stub';
    }

    protected function commonName(): string
    {
        return 'Test';
    }
}

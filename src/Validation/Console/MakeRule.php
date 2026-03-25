<?php

declare(strict_types=1);

namespace Phenix\Validation\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeRule extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:rule';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new validation rule.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new validation rule.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The rule class name');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create rule');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Validation' . DIRECTORY_SEPARATOR . 'Rules';
    }

    protected function stub(): string
    {
        return 'rule.stub';
    }

    protected function commonName(): string
    {
        return 'Rule';
    }
}

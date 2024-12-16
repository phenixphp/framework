<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeQuery extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:query';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new query.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new query.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The query name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create queries');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Queries';
    }

    protected function stub(): string
    {
        return 'query.stub';
    }

    protected function commonName(): string
    {
        return 'Query';
    }
}

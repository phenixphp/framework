<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

class MakeServiceProvider extends CommonMaker
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

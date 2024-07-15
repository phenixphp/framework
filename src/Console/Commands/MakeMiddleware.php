<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

class MakeMiddleware extends CommonMaker
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

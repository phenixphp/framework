<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

class MakeRequest extends CommonMaker
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

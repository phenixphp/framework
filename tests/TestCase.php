<?php

declare(strict_types=1);

namespace Tests;

use Phenix\Testing\TestCase as TestingTestCase;

class TestCase extends TestingTestCase
{
    protected function getAppDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'application';
    }
}

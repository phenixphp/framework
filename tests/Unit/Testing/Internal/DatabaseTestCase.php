<?php

declare(strict_types=1);

namespace Tests\Unit\Testing\Internal;

use Phenix\Testing\Concerns\RefreshDatabase;
use Phenix\Testing\Concerns\WithFaker;
use Phenix\Testing\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
}

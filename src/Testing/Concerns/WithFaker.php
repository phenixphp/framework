<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    protected Generator|null $faker = null;

    protected function faker(): Generator
    {
        return $this->faker ??= Factory::create();
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Database;

use Faker\Factory;
use Faker\Generator;
use Phinx\Seed\AbstractSeed;

abstract class Seed extends AbstractSeed
{
    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create(Factory::DEFAULT_LOCALE);
    }
}

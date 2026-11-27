<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Faker\Generator;
use Tests\Unit\Testing\Internal\DatabaseTestCase;

class DatabaseTestCaseTest extends DatabaseTestCase
{
    private int $migrations = 0;
    private int $truncations = 0;

    public function test_it_refreshes_the_database_and_provides_faker(): void
    {
        self::assertSame(1, $this->migrations);
        self::assertSame(1, $this->truncations);

        $this->refreshDatabase();

        self::assertSame(1, $this->migrations);
        self::assertSame(2, $this->truncations);
        self::assertInstanceOf(Generator::class, $this->faker());
        self::assertSame($this->faker(), $this->faker());
    }

    protected function getAppDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'application';
    }

    protected function runMigrations(): void
    {
        $this->migrations++;
    }

    protected function truncateDatabase(): void
    {
        $this->truncations++;
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Contracts;

interface App
{
    public function run(): void;

    public function stop(): void;

    public function swap(string $key, object $concrete): void;
}

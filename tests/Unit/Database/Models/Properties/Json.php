<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Models\Properties;

class Json
{
    protected array $data;

    public function __construct(string $data)
    {
        $this->data = json_decode($data, true);
    }

    public function getData(): array
    {
        return $this->data;
    }
}

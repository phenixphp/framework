<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Char;
use Phenix\Database\Migrations\Columns\Str;
use Phenix\Database\Migrations\Columns\Text;

trait WithText
{
    public function string(string $name, int $limit = 255): Str
    {
        return $this->addColumnWithAdapter(new Str($name, $limit));
    }

    public function text(string $name, int|null $limit = null): Text
    {
        return $this->addColumnWithAdapter(new Text($name, $limit));
    }

    public function char(string $name, int $limit = 255): Char
    {
        return $this->addColumnWithAdapter(new Char($name, $limit));
    }
}

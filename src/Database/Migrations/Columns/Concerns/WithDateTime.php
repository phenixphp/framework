<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Date;
use Phenix\Database\Migrations\Columns\DateTime;
use Phenix\Database\Migrations\Columns\Interval;
use Phenix\Database\Migrations\Columns\Time;
use Phenix\Database\Migrations\Columns\Timestamp;

trait WithDateTime
{
    public function dateTime(string $name): DateTime
    {
        return $this->addColumnWithAdapter(new DateTime($name));
    }

    public function date(string $name): Date
    {
        return $this->addColumnWithAdapter(new Date($name));
    }

    public function time(string $name, bool $timezone = false): Time
    {
        return $this->addColumnWithAdapter(new Time($name, $timezone));
    }

    public function timestamp(string $name, bool $timezone = false): Timestamp
    {
        return $this->addColumnWithAdapter(new Timestamp($name, $timezone));
    }

    public function interval(string $name): Interval
    {
        return $this->addColumnWithAdapter(new Interval($name));
    }
}

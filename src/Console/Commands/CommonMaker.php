<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class CommonMaker extends Maker
{
    protected function configure(): void
    {
        $commonName = strtolower($this->commonName());

        $this->setHelp("This command allows you to create a new {$commonName}.");

        $this->addArgument('name', InputArgument::REQUIRED, "The {$commonName} name");

        $this->addOption('force', 'f', InputOption::VALUE_NONE, "Force to create {$commonName}");
    }
}

<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\App;
use Phenix\Packages\PackageManifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PackageDiscover extends Command
{
    protected static $defaultName = 'package:discover';

    protected static $defaultDescription = 'Rebuild the cached package manifest.';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var PackageManifest $manifest */
        $manifest = App::make(PackageManifest::class);
        $manifest->build();

        $output->writeln('<info>Discovered packages:</info>');

        foreach ($manifest->packages() as $package) {
            $output->writeln("  <comment>{$package}</comment>");
        }

        return Command::SUCCESS;
    }
}

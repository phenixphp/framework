<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use FilesystemIterator;
use Phenix\App;
use Phenix\Exceptions\RuntimeException;
use Phenix\Packages\PackageRegistry;
use Phenix\Util\Path;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function is_string;

class VendorPublish extends Command
{
    protected static $defaultName = 'vendor:publish';

    protected static $defaultDescription = 'Publish resources from installed packages.';

    protected function configure(): void
    {
        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Service provider to publish.');
        $this->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Resource tag to publish.');
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Publish resources from all providers.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var PackageRegistry $registry */
        $registry = App::make(PackageRegistry::class);
        $provider = $input->getOption('provider');
        $tag = $input->getOption('tag');

        if (! $input->getOption('all') && $provider === null && $tag === null) {
            $output->writeln('<error>Specify --provider, --tag, or --all.</error>');

            return Command::FAILURE;
        }

        $paths = $registry->pathsToPublish(
            is_string($provider) ? $provider : null,
            is_string($tag) ? $tag : null,
        );

        if ($paths === []) {
            $output->writeln('<comment>No publishable resources were found.</comment>');

            return Command::SUCCESS;
        }

        $commandStatus = Command::SUCCESS;

        foreach ($paths as $source => $destination) {
            try {
                $published = $this->publish(
                    (string) $source,
                    (string) $destination,
                    (bool) $input->getOption('force'),
                );
                $status = $published > 0 ? '<info>Published:</info>' : '<comment>Skipped:</comment>';
                $output->writeln("{$status} {$source} <comment>→</comment> {$destination}");
            } catch (Throwable $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');

                $commandStatus = Command::FAILURE;
            }
        }

        return $commandStatus;
    }

    private function publish(string $source, string $destination, bool $force): int
    {
        if (is_file($source)) {
            return $this->copyFile($source, $this->migrationDestination($source, $destination), $force) ? 1 : 0;
        }

        if (! is_dir($source)) {
            throw new RuntimeException("Publish source {$source} does not exist.");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        );
        $published = 0;

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($source) + 1);
            $target = Path::join(rtrim($destination, DIRECTORY_SEPARATOR), $relative);

            if ($this->copyFile($file->getPathname(), $this->migrationDestination($file->getPathname(), $target), $force)) {
                $published++;
            }
        }

        return $published;
    }

    private function copyFile(string $source, string $destination, bool $force): bool
    {
        if (is_file($destination) && ! $force) {
            return false;
        }

        $directory = dirname($destination);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create publish directory {$directory}.");
        }

        if (! @copy($source, $destination)) {
            throw new RuntimeException("Unable to publish {$source} to {$destination}.");
        }

        return true;
    }

    private function migrationDestination(string $source, string $destination): string
    {
        $name = basename($destination);

        if (! str_contains(str_replace('\\', '/', $source), '/migrations/')
            || preg_match('/^\d{14}_/', $name) !== 1) {
            return $destination;
        }

        return Path::join(dirname($destination), date('YmdHis') . '_' . substr($name, 15));
    }
}

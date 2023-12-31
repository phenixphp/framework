<?php

declare(strict_types=1);

namespace Phenix\Console;

use Phenix\Facades\File;
use Phenix\Util\NamespaceResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Maker extends Command
{
    protected const EMPTY_LINE = '';
    protected const SEARCH = ['{namespace}', '{name}'];

    protected InputInterface $input;

    abstract protected function outputDirectory(): string;

    abstract protected function stub(): string;

    abstract protected function commonName(): string;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;

        $name = $this->input->getArgument('name');
        $force = $this->input->getOption('force');

        $namespace = explode(DIRECTORY_SEPARATOR, $name);
        $className = array_pop($namespace);
        $fileName = $this->getCustomFileName() ?? $className;

        $filePath = $this->preparePath($namespace) . DIRECTORY_SEPARATOR . "{$fileName}.php";
        $namespace = $this->prepareNamespace($namespace);

        if (File::exists($filePath) && ! $force) {
            $output->writeln(["<comment>{$this->commonName()} already exists!</comment>", self::EMPTY_LINE]);

            return Command::SUCCESS;
        }

        $stub = $this->getStubContent();
        $stub = str_replace(self::SEARCH, [$namespace, $className], $stub);

        File::put($filePath, $stub);

        $output->writeln(["<info>{$this->commonName()} successfully generated!</info>", self::EMPTY_LINE]);

        return Command::SUCCESS;
    }

    /**
     * @param array<int, string> $namespace
     */
    protected function preparePath(array $namespace): string
    {
        $path = base_path($this->outputDirectory());

        $this->checkDirectory($path);

        foreach ($namespace as $directory) {
            $path .= DIRECTORY_SEPARATOR . ucfirst($directory);

            $this->checkDirectory($path);
        }

        return $path;
    }

    protected function checkDirectory(string $path): void
    {
        if (! File::exists($path)) {
            File::createDirectory($path);
        }
    }

    /**
     * @param array<int, string> $namespace
     */
    protected function prepareNamespace(array $namespace): string
    {
        array_unshift($namespace, NamespaceResolver::parse($this->outputDirectory()));

        return implode('\\', $namespace);
    }

    protected function getCustomFileName(): string|null
    {
        return null;
    }

    protected function getStubContent(): string
    {
        $path = dirname(__DIR__)
            . DIRECTORY_SEPARATOR . 'stubs'
            . DIRECTORY_SEPARATOR . $this->stub();

        return File::get($path);
    }
}

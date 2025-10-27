<?php

declare(strict_types=1);

namespace Phenix\Mail\Console;

use Phenix\Console\Maker;
use Phenix\Facades\File;
use Phenix\Util\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'make:mail',
    description: 'Create a new mailable class'
)]
class MakeMail extends Maker
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the mailable class');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create mailable');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;

        $name = $this->input->getArgument('name');
        $force = $this->input->getOption('force');

        $namespace = explode(DIRECTORY_SEPARATOR, $name);
        $className = array_pop($namespace);
        $fileName = $this->getCustomFileName() ?? $className;

        $filePath = $this->preparePath($namespace) . DIRECTORY_SEPARATOR . "{$fileName}.php";
        $namespaceString = $this->prepareNamespace($namespace);

        if (File::exists($filePath) && ! $force) {
            $output->writeln(["<comment>{$this->commonName()} already exists!</comment>", self::EMPTY_LINE]);

            return Command::SUCCESS;
        }

        $viewName = Str::snake($className);
        $viewDotPath = empty($namespace)
            ? $viewName
            : implode('.', array_map('strtolower', $namespace)) . ".{$viewName}";

        $stub = $this->getStubContent();
        $stub = str_replace(['{namespace}', '{name}', '{view}'], [$namespaceString, $className, $viewDotPath], $stub);

        File::put($filePath, $stub);

        $outputPath = str_replace(base_path(), '', $filePath);

        $output->writeln(["<info>{$this->commonName()} [{$outputPath}] successfully generated!</info>", self::EMPTY_LINE]);

        $this->createView($input, $output, $namespace, $viewName);

        return Command::SUCCESS;
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Mail';
    }

    protected function commonName(): string
    {
        return 'Mailable';
    }

    protected function stub(): string
    {
        return 'mailable.stub';
    }

    protected function createView(InputInterface $input, OutputInterface $output, array $namespace, string $viewName): void
    {
        $force = $input->getOption('force');

        $viewPath = base_path('resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'emails');
        $this->checkDirectory($viewPath);

        foreach ($namespace as $directory) {
            $viewPath .= DIRECTORY_SEPARATOR . strtolower($directory);
            $this->checkDirectory($viewPath);
        }

        $viewFilePath = $viewPath . DIRECTORY_SEPARATOR . "{$viewName}.php";

        if (File::exists($viewFilePath) && ! $force) {
            $output->writeln(["<comment>View already exists!</comment>", self::EMPTY_LINE]);

            return;
        }

        $viewStub = $this->getViewStubContent();
        $viewStub = str_replace('{title}', ucwords(str_replace('_', ' ', $viewName)), $viewStub);

        File::put($viewFilePath, $viewStub);

        $outputPath = str_replace(base_path(), '', $viewFilePath);

        $output->writeln(["<info>View [{$outputPath}] successfully generated!</info>", self::EMPTY_LINE]);
    }

    protected function getViewStubContent(): string
    {
        $path = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'stubs'
            . DIRECTORY_SEPARATOR . 'mail-view.stub';

        return File::get($path);
    }
}

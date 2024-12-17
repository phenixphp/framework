<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Facades\File;
use Phenix\Console\Maker;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModel extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'make:model';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates a new model.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create a new model.');

        $this->addArgument('name', InputArgument::REQUIRED, 'The model name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create model');

        $this->addOption('collection', 'cn', InputOption::VALUE_NONE, 'Create a collection for the model');

        $this->addOption('query', 'qb', InputOption::VALUE_NONE, 'Create a query builder for the model');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Models';
    }

    protected function stub(): string
    {
        if ($this->input->getOption('collection')) {
            return 'model.collection.stub';
        } elseif ($this->input->getOption('query')) {
            return 'model.query.stub';
        } else {
            return 'model.stub';
        }
    }

    protected function commonName(): string
    {
        return 'Model';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;

        $search = parent::SEARCH;

        $name = $this->input->getArgument('name');
        $force = $this->input->getOption('force');
        $withCollection = $input->getOption('collection');
        $withQuery = $input->getOption('query');

        $namespace = explode(DIRECTORY_SEPARATOR, $name);
        $className = array_pop($namespace);
        $fileName = $this->getCustomFileName() ?? $className;

        $filePath = $this->preparePath($namespace) . DIRECTORY_SEPARATOR . "{$fileName}.php";
        $namespace = $this->prepareNamespace($namespace);

        $replace = [$namespace, $className];

        if (File::exists($filePath) && ! $force) {
            $output->writeln(["<comment>{$this->commonName()} already exists!</comment>", self::EMPTY_LINE]);

            return parent::SUCCESS;
        }

        $application = $this->getApplication();

        if ($withCollection) {
            $command = $application->find('make:collection');
            $collectionName = "{$name}Collection";

            $arguments = new ArrayInput([
                'name' => $collectionName,
            ]);

            $command->run($arguments, $output);

            $search[] = '{collection_name}';
            $replace[] = $collectionName;
        } elseif ($withQuery) {
            $command = $application->find('make:query');
            $queryName = "{$name}Query";

            $arguments = new ArrayInput([
                'name' => $queryName,
            ]);

            $command->run($arguments, $output);

            $search[] = '{query_name}';
            $replace[] = $queryName;
        }

        $stub = $this->getStubContent();
        $stub = str_replace($search, $replace, $stub);

        File::put($filePath, $stub);

        $output->writeln(["<info>{$this->commonName()} successfully generated!</info>", self::EMPTY_LINE]);


        return parent::SUCCESS;
    }
}


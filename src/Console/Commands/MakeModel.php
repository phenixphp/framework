<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Facades\File;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class MakeModel extends CommonMaker
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
        parent::configure();

        $this->addOption('collection', 'cn', InputOption::VALUE_NONE, 'Create a collection for the model');
        $this->addOption('query', 'qb', InputOption::VALUE_NONE, 'Create a query builder for the model');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Create a model with custom query builder, collection, and migration');
        $this->addOption('migration', 'm', InputOption::VALUE_REQUIRED, 'Create a migration for the model');
        $this->addOption('controller', 'c', InputOption::VALUE_NONE, 'Create a controller for the model');
    }

    protected function outputDirectory(): string
    {
        return 'app' . DIRECTORY_SEPARATOR . 'Models';
    }

    protected function stub(): string
    {
        $stub = 'model.stub';

        if ($this->input->getOption('all')) {
            $stub = 'model.all.stub';
        } elseif ($this->input->getOption('collection')) {
            $stub = 'model.collection.stub';
        } elseif ($this->input->getOption('query')) {
            $stub = 'model.query.stub';
        }

        return $stub;
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

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

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

        if ($input->getOption('collection') || $input->getOption('all')) {
            $command = $application->find('make:collection');
            $collectionName = "{$name}Collection";

            $arguments = new ArrayInput([
                'name' => $collectionName,
            ]);

            $command->run($arguments, $output);

            $search[] = '{collection_name}';
            $replace[] = $collectionName;
        }

        if ($input->getOption('query') || $input->getOption('all')) {
            $command = $application->find('make:query');
            $queryName = "{$name}Query";

            $arguments = new ArrayInput([
                'name' => $queryName,
            ]);

            $command->run($arguments, $output);

            $search[] = '{query_name}';
            $replace[] = $queryName;
        }

        if ($input->getOption('migration') || $input->getOption('all')) {
            $question = new Question('Enter migration name');

            $migrationName = $questionHelper->ask($input, $output, $question);

            $command = $application->find('make:migration');

            $arguments = new ArrayInput([
                'name' => $migrationName,
            ]);

            $command->run($arguments, $output);
        }

        if ($input->getOption('controller') || $input->getOption('all')) {
            $command = $application->find('make:controller');
            $controllerName = "{$name}Controller";

            $arguments = new ArrayInput([
                'name' => $controllerName,
            ]);

            $command->run($arguments, $output);
        }

        $stub = $this->getStubContent();
        $stub = str_replace($search, $replace, $stub);

        File::put($filePath, $stub);

        $output->writeln(["<info>{$this->commonName()} successfully generated!</info>", self::EMPTY_LINE]);

        return parent::SUCCESS;
    }
}

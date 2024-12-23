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

    protected array $search = parent::SEARCH;

    protected array $replace;

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

        $name = $this->input->getArgument('name');
        $force = $this->input->getOption('force');

        $namespace = explode(DIRECTORY_SEPARATOR, $name);
        $className = array_pop($namespace);
        $fileName = $this->getCustomFileName() ?? $className;

        $filePath = $this->preparePath($namespace) . DIRECTORY_SEPARATOR . "{$fileName}.php";
        $namespace = $this->prepareNamespace($namespace);

        $this->replace = [$namespace, $className];

        if (File::exists($filePath) && ! $force) {
            $output->writeln(["<comment>{$this->commonName()} already exists!</comment>", self::EMPTY_LINE]);

            return parent::SUCCESS;
        }

        $this->executeCommands($input, $output, $name);

        $stub = $this->getStubContent();
        $stub = str_replace($this->search, $this->replace, $stub);

        File::put($filePath, $stub);

        $output->writeln(["<info>{$this->commonName()} successfully generated!</info>", self::EMPTY_LINE]);

        return parent::SUCCESS;
    }

    protected function executeCommands(InputInterface $input, OutputInterface $output, string $name): void
    {
        $application = $this->getApplication();

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        foreach ($this->getCommandOptions() as $option => $task) {
            if ($input->getOption($option) || $input->getOption('all')) {
                $command = $application->find($task['command']);
                $taskName = $task['name_suffix'] ? "{$name}{$task['name_suffix']}" : $name;

                if (isset($task['ask_name']) && $task['ask_name']) {
                    $question = new Question($task['question']);
                    $taskName = $questionHelper->ask($input, $output, $question);
                }

                $arguments = new ArrayInput([
                    'name' => $taskName,
                ]);

                $command->run($arguments, $output);

                if ($task['search_key']) {
                    $this->search[] = $task['search_key'];
                    $this->replace[] = $taskName;
                }
            }
        }
    }

    protected function getCommandOptions(): array
    {
        return [
            'collection' => [
                'command' => 'make:collection',
                'name_suffix' => 'Collection',
                'search_key' => '{collection_name}',
            ],
            'query' => [
                'command' => 'make:query',
                'name_suffix' => 'Query',
                'search_key' => '{query_name}',
            ],
            'migration' => [
                'command' => 'make:migration',
                'name_suffix' => '',
                'search_key' => '',
                'ask_name' => true,
                'question' => 'Enter migration name',
            ],
            'controller' => [
                'command' => 'make:controller',
                'name_suffix' => 'Controller',
                'search_key' => '',
            ],
        ];
    }
}

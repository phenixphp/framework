<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Facades\Config;
use Phenix\Facades\File;
use Phenix\Facades\View;
use Phenix\Tasks\WorkerPool;
use Phenix\Views\Tasks\CompileTemplates;
use Phenix\Views\ViewName;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ViewCache extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'view:cache';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Compiled all available views';

    protected array $tasks = [];

    protected function configure(): void
    {
        $this->setHelp('This command allows you to compile all available views.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->tasks = [];

        View::clearCache();

        $this->compile(Config::get('view.path'));

        WorkerPool::batch($this->tasks);

        $output->writeln('<info>All views were compiled successfully!.</info>');

        return Command::SUCCESS;
    }

    private function compile(string $path): void
    {
        $templates = [];
        $directories = [];

        foreach (File::listFiles($path) as $file) {
            if (File::isDirectory($file)) {
                $directories[] = $file;
            } else {
                $templates[] = ViewName::template($file, Config::get('view.path'));
            }
        }

        $this->tasks[] = new CompileTemplates($templates);

        $templates = [];

        foreach ($directories as $directory) {
            $this->compile($directory);
        }
    }
}

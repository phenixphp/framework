<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Facades\Config;
use Phenix\Facades\File;
use Phenix\Facades\View;
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

    protected function configure(): void
    {
        $this->setHelp('This command allows you to compile all available views.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        View::clearCache();

        $this->pushTask(Config::get('view.path'));

        $output->writeln('<info>All views were compiled successfully!.</info>');

        return Command::SUCCESS;
    }

    private function pushTask(string $path): void
    {
        $directories = [];

        foreach (File::listFiles($path) as $file) {
            if (File::isDirectory($file)) {
                $directories[] = $file;
            } else {
                $template = ViewName::template($file, Config::get('view.path'));

                View::compile($template);
            }
        }

        foreach ($directories as $directory) {
            $this->pushTask($directory);
        }
    }
}

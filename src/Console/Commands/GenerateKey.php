<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Facades\Config;
use Phenix\Facades\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKey extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'key:generate';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Set the application key.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to generate and set the application key.');

        $this->addArgument('environment', InputArgument::OPTIONAL, 'The environment file', '.env');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create queries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        $currentKey = Config::get('app.key');

        if ($currentKey && ! $force) {
            $output->writeln('<error>Application key is already set. Use --force to override it.</error>');

            return Command::FAILURE;
        }

        $key = 'base64:' . base64_encode(random_bytes(32));
        $environment = $input->getArgument('environment');

        $environmentData = File::get(base_path($environment));

        $replaced = preg_replace(
            "/^APP_KEY=.*$/m",
            "APP_KEY={$key}",
            $environmentData
        );

        if (empty($replaced) || $replaced === $environmentData) {
            $output->writeln('<error>Failed to set the application key.</error>');

            return Command::FAILURE;
        }

        File::put(base_path($environment), $replaced);

        $output->writeln('<info>Application key set successfully!.</info>');

        return Command::SUCCESS;
    }
}

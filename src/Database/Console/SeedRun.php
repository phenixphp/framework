<?php

declare(strict_types=1);

namespace Phenix\Database\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'seed:run')]
class SeedRun extends DatabaseCommand
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'seed:run';

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment');

        $this->setDescription('Run database seeders')
            ->addOption('--seed', '-s', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'What is the name of the seeder?')
            ->setHelp(
                <<<EOT
The <info>seed:run</info> command runs all available or individual seeders

<info>php phenix seed:run </info>
<info>php phenix seed:run -s UsersSeeder</info>
<info>php phenix seed:run -s UsersSeeder -s PermissionsSeeder -s LogsSeeder</info>
<info>php phenix seed:run -v</info>

EOT
            );
    }

    /**
     * Run database seeders.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     * @return int integer 0 on success, or an error code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap($input, $output);

        /** @var array<string>|null $seedSet */
        $seedSet = $input->getOption('seed');
        /** @var string|null $environment */
        $environment = $input->getOption('environment');

        if ($environment === null) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment, $this->verbosityLevel);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment, $this->verbosityLevel);
        }

        $envOptions = $this->getConfig()->getEnvironment($environment);

        if (isset($envOptions['name'])) {
            $output->writeln('<info>using database</info> ' . $envOptions['name'], $this->verbosityLevel);
        } else {
            $output->writeln('<error>Could not determine database name! Please specify a database name in your config file.</error>');

            return self::CODE_ERROR;
        }

        $start = microtime(true);

        if (empty($seedSet)) {
            // run all the seed(ers)
            $this->getManager()->seed($environment);
        } else {
            // run seed(ers) specified in a comma-separated list of classes
            foreach ($seedSet as $seed) {
                $this->getManager()->seed($environment, trim($seed));
            }
        }

        $end = microtime(true);

        $output->writeln('', $this->verbosityLevel);
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>', $this->verbosityLevel);

        return self::CODE_SUCCESS;
    }
}

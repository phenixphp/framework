<?php

declare(strict_types=1);

namespace Phenix\Routing\Console;

use Phenix\App;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Routing\Route;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RouteList extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'route:list';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'List all registered routes';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to list all registered routes...')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the route to list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Route $router */
        $router = App::make(Route::class);

        $routes = $router->toArray();

        foreach ($routes as $route) {
            /** @var HttpMethod $httpMethod */
            [$httpMethod, $path, , , $routeName, ] = $route;

            $output->writeln(sprintf(
                '<info>%s</info> %s (%s)',
                $httpMethod->value,
                $path,
                $routeName ?? '',
            ));
        }

        return Command::SUCCESS;
    }
}

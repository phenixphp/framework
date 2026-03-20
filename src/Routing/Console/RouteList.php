<?php

declare(strict_types=1);

namespace Phenix\Routing\Console;

use Phenix\App;
use Phenix\Http\Constants\HttpMethod;
use Phenix\Routing\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Filter by route name (supports partial match)')
            ->addOption('method', null, InputOption::VALUE_REQUIRED, 'Filter by HTTP method')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Filter by path (supports partial match)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output routes as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Router $router */
        $router = App::make(Router::class);

        $routes = $router->toArray();

        $routes = $this->filterRoutes(
            $routes,
            (string) $input->getOption('name'),
            (string) $input->getOption('method'),
            (string) $input->getOption('path')
        );

        if ($input->getOption('json')) {
            $this->renderJson($output, $routes);

            return Command::SUCCESS;
        }

        $this->renderTable($output, $routes);

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array> $routes
     * @return array<int, array>
     */
    private function filterRoutes(array $routes, string|null $name, string|null $method, string|null $path): array
    {
        return array_values(array_filter($routes, function (array $route) use ($name, $method, $path): bool {
            /** @var HttpMethod $routeMethod */
            [$routeMethod, $routePath, , , $routeName, ] = $route;

            $match = true;

            if ($method && strcasecmp($routeMethod->value, $method) !== 0) {
                $match = false;
            }

            if ($match && $name && $routeName && ! str_contains($routeName, $name)) {
                $match = false;
            }

            if ($match && $path && ! str_contains($routePath, $path)) {
                $match = false;
            }

            return $match;
        }));
    }

    /**
     * @param array<int, array> $routes
     */
    private function renderJson(OutputInterface $output, array $routes): void
    {
        $json = array_map(function (array $route) {
            /** @var HttpMethod $method */
            [$method, $path, , $middlewares, $name, $params] = $route;

            return [
                'method' => $method->value,
                'path' => $path,
                'name' => $name ?: null,
                'middlewares' => array_map(fn ($mw): string => is_object($mw) ? $mw::class : (string) $mw, $middlewares),
                'params' => $params,
            ];
        }, $routes);

        $output->writeln(json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * @param array<int, array> $routes
     */
    private function renderTable(OutputInterface $output, array $routes): void
    {
        $table = new Table($output);
        $table->setHeaders(['Method', 'Path', 'Name', 'Middleware', 'Params']);

        foreach ($routes as $route) {
            /** @var HttpMethod $method */
            [$method, $path, , $middlewares, $name, $params] = $route;

            $table->addRow([
                sprintf('<info>%-6s</info>', $method->value),
                $path,
                $name ?: '',
                implode(',', array_map(fn ($mw): string => is_object($mw) ? basename(str_replace('\\', '/', $mw::class)) : (string) $mw, $middlewares)),
                empty($params) ? '' : implode(',', $params),
            ]);
        }

        $table->render();
    }
}

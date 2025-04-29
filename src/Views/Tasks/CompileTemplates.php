<?php

declare(strict_types=1);

namespace Phenix\Views\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Facades\Config;
use Phenix\Facades\View;
use Phenix\Tasks\AppParallelTask;
use Phenix\Views\ViewName;

class CompileTemplates extends AppParallelTask
{
    protected string $basePath;

    public function __construct(
        private array $paths
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): bool
    {
        foreach ($this->paths as $path) {
            $template = ViewName::template($path, Config::get('view.path'));

            View::compile($template);
        }

        return true;
    }
}

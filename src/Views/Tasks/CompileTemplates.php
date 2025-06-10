<?php

declare(strict_types=1);

namespace Phenix\Views\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Facades\Config;
use Phenix\Facades\View;
use Phenix\Tasks\Result;
use Phenix\Tasks\Task;
use Phenix\Views\ViewName;

class CompileTemplates extends Task
{
    protected string $basePath;

    public function __construct(
        private array $paths
    ) {}

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        foreach ($this->paths as $path) {
            $template = ViewName::template($path, Config::get('view.path'));

            View::compile($template);
        }

        return Result::success();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Amp\Http\Server\Response;
use Phenix\Http\Controller;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        return response()->plain('Hello, world!' . PHP_EOL);
    }
}

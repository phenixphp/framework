<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Phenix\Http\Controller;
use Phenix\Http\Response;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        return response()->plain('Hello, world!' . PHP_EOL);
    }
}

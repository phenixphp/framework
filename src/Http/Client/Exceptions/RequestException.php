<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Exceptions;

use Phenix\Http\Client\Response;
use RuntimeException;

use function sprintf;

class RequestException extends RuntimeException
{
    public function __construct(private readonly Response $response)
    {
        parent::__construct(sprintf(
            'HTTP request returned status code %d.',
            $response->status()
        ), $response->status());
    }

    public function response(): Response
    {
        return $this->response;
    }
}

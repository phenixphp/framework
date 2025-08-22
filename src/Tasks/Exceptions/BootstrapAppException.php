<?php

declare(strict_types=1);

namespace Phenix\Tasks\Exceptions;

use Exception;

class BootstrapAppException extends Exception
{
    // This exception is thrown when the application fails to boot in task context.
}

<?php

namespace App\Core\Exceptions;

use Exception;
use Throwable;

class UnauthorizedException extends Exception
{
    public function __construct(string $message = 'Unauthorized access', int $code = 401, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

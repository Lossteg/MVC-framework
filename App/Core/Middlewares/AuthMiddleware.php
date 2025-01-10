<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Exceptions\UnauthorizedException;

class AuthMiddleware
{
    public static function check(): void
    {
        session_start();

        if (!isset($_SESSION['username'])) {
            throw new UnauthorizedException('Unauthorized access. Please log in.');
        }
    }
}

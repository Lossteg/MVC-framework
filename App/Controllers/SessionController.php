<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Attributes\Get;
use App\Core\Attributes\Post;
use App\Core\View\View;

class SessionController
{
    #[Get('/')]
    public function index(): string
    {
        session_start();

        if (isset($_SESSION['username'])) {
            header('Location: /orders');
            exit;
        }

        return View::make('login/index');
    }

    #[Post('/')]
    public function login(): string
    {
        if (!empty($_POST['username'])) {
            session_start();
            $_SESSION['username'] = $_POST['username'];
            header('Location: /orders');
            exit;
        }

        header('Location: /');
        exit;
    }

    #[Get('/logout')]
    public function logout(): void
    {
        session_start();

        $_SESSION = [];
        session_destroy();

        header('Location: /');
        exit;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Attributes\Get;
use App\Core\View;
use App\Services\Contracts\UserServiceInterface;

class UserController
{
    public function __construct(private UserServiceInterface $userService)
    {}

    #[Get('/user')]
    public function index(): void
    {
        $users = $this->userService->getAllUsers();
        echo View::make('user', compact('users')) . "<p>Working fine</p>";
    }

    public function create(): void
    {
        echo View::make('user');
    }

    public function store(array $data): void
    {
        $this->userService->createUser($data);
        header('Location: /user');
    }
}

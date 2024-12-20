<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\UserServiceInterface;

class UserService implements UserServiceInterface
{
    private array $users = [];

    public function getAllUsers(): array
    {
        return $this->users;
    }

    public function createUser(array $data): array
    {
        $newUser = [
            'id' => count($this->users) + 1,
            'name' => $data['name'],
            'email' => $data['email']
        ];
        $this->users[] = $newUser;
        return $newUser;
    }

    public function updateUser(int $id, array $data): ?array
    {
        foreach ($this->users as &$user) {
            if ($user['id'] === $id) {
                $user['name'] = $data['name'] ?? $user['name'];
                $user['email'] = $data['email'] ?? $user['email'];
                return $user;
            }
        }
        return null;
    }
}

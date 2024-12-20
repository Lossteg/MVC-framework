<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface UserServiceInterface
{
    public function getAllUsers(): array;
    public function createUser(array $data): array;
    public function updateUser(int $id, array $data): ?array;
}
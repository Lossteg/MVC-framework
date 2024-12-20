<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface OrderServiceInterface
{
    public function getAllOrders(): array;
    public function createOrder(array $data): array;
    public function updateOrder(int $id, array $data): ?array;
}
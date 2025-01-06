<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface OrderServiceInterface
{
    public function getOrderById(int $id): ?array;
    public function getAllOrders(): array;
    public function createOrder(array $data): array;
}
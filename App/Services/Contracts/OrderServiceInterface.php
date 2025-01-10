<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface OrderServiceInterface
{
    /**
     * @return array{id: int, user_id: int, products: string, total: float, status: string}|null
     */
    public function getOrderById(int $id): ?array;

    /**
     * @return array<int, array{id: int, user_id: int, products: string, total: float, status: string}>
     */
    public function getAllOrders(): array;

    /**
     * @param array{user_id?: int|string, products?: string, total?: float|string} $data
     * @return array{id: int, user_id: int, products: string, total: float, status: string}
     */
    public function createOrder(array $data): array;
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\OrderServiceInterface;

class OrderService implements OrderServiceInterface
{
    private array $orders = [];

    public function getAllOrders(): array
    {
        return $this->orders;
    }

    public function createOrder(array $data): array
    {
        $newOrder = [
            'id' => count($this->orders) + 1,
            'user_id' => (int)$data['user_id'],
            'products' => $data['products'],
            'total' => (float)$data['total'],
            'status' => 'pending'
        ];
        $this->orders[] = $newOrder;
        return $newOrder;
    }

    public function updateOrder(int $id, array $data): ?array
    {
        foreach ($this->orders as &$order) {
            if ($order['id'] === $id) {
                $order['status'] = $data['status'] ?? $order['status'];
                $order['total'] = isset($data['total']) ? (float)$data['total'] : $order['total'];
                return $order;
            }
        }
        return null;
    }
}
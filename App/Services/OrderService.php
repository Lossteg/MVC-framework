<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\OrderServiceInterface;

class OrderService implements OrderServiceInterface
{
    private array $orders = [
        [
            'id' => 1,
            'user_id' => 1,
            'products' => 'apple, banana',
            'total' => 20.50,
            'status' => 'pending',
        ],
        [
            'id' => 2,
            'user_id' => 2,
            'products' => 'orange, mango',
            'total' => 15.75,
            'status' => 'completed',
        ],
    ];

    public function getOrderById(int $id): ?array
    {
        foreach ($this->orders as $order) {
            if ($order['id'] === $id) {
                return $order;
            }
        }
        return null;
    }

    public function getAllOrders(): array
    {
        return $this->orders;
    }

    public function createOrder(array $data): array
    {
        $newOrder = [
            'id' => count($this->orders) + 1,
            'user_id' => isset($data['user_id']) ? (int)$data['user_id'] : 0,
            'products' => $data['products'] ?? '',
            'total' => isset($data['total']) ? (float)$data['total'] : 0.0,
            'status' => 'pending',
        ];
        $this->orders[] = $newOrder;
        return $newOrder;
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Attributes\Get;
use App\Core\View;
use App\Services\Contracts\OrderServiceInterface;

class OrderController
{
    public function __construct(private OrderServiceInterface $orderService)
    {}

    #[Get('/order')]
    public function index(): void
    {
        $orders = $this->orderService->getAllOrders();
        echo View::make('order', compact($orders)) . "<p>Working fine</p>";

    }

    public function create(): void
    {
        echo View::make('order', 'create');
    }

    public function store(array $data): void
    {
        $this->orderService->createOrder($data);
        header('Location: /order');
    }
}

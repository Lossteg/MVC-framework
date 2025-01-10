<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Attributes\AuthorizedAccess;
use App\Core\Attributes\Get;
use App\Core\Attributes\Post;
use App\Core\Attributes\RouteGroup;
use App\Core\View\View;
use App\Services\Contracts\OrderServiceInterface;

#[RouteGroup('/orders')]
#[AuthorizedAccess]
readonly class OrderController
{
    public function __construct(private readonly OrderServiceInterface $orderService)
    {
    }

    #[Get('/{id}')]
    public function getOrder(int $id): string
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            http_response_code(404);
            return View::make('error/404');
        }

        return View::make('orders/show', ['order' => $order]);
    }

    #[Get('/')]
    public function getAllOrders(): string
    {
        $orders = $this->orderService->getAllOrders();
        return View::make('orders/index', ['orders' => $orders]);
    }

    #[Post('/create/')]
    public function createOrder(): void
    {
        $data = $_POST;
        $this->orderService->createOrder($data);
        echo '<br /> create order success';
        echo '<pre />';
        print_r($data);
        echo '</pre>';
    }

    #[Get('/create')]
    public function showCreateForm(): string
    {
        return View::make('orders/create');
    }
}

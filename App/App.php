<?php

declare(strict_types=1);

namespace App;

use App\Core\DI\Container;
use App\Core\Router\Router;
use App\Core\View;
use App\Core\Exceptions\RouteNotFoundException;
use App\Services\Contracts\OrderServiceInterface;
use App\Services\Contracts\ProductServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\UserService;

class App
{
    public function __construct(
        protected Container $container,
        protected Router $router,
        protected array $request,
    ) {
        $this->container ->set(OrderServiceInterface::class, OrderService::class);
    }

    public function run(): void
    {
        try {
            echo $this->router->resolve($this->request['uri'], strtolower($this->request['method']));
        } catch (RouteNotFoundException) {
            http_response_code(404);

            echo View::make('error/404');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Core\Bootstrap;

use App\Core\DI\Container;
use App\Core\Exceptions\RouteNotFoundException;
use App\Core\Router\Router;
use App\Core\View\View;
use App\Services\Contracts\OrderServiceInterface;
use App\Services\OrderService;

readonly class App
{
    public function __construct(
        protected Container $container,
        protected Router $router,
        /**
         * @var array{uri: string, method: string}
         */
        protected array $request,
    ) {
        $this->container->set(OrderServiceInterface::class, OrderService::class);
        $router->autoloadRoutes();
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

<?php

declare(strict_types=1);

namespace App\Core\Bootstrap;

use App\Core\DI\Container;
use App\Core\Router\Router;

$container = new Container();
$router = new Router($container);

(new App(
    $container,
    $router,
    ['uri' => $_SERVER['REQUEST_URI'], 'method' => $_SERVER['REQUEST_METHOD']],
))->run();

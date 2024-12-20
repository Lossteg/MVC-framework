<?php

declare(strict_types=1);

use App\App;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Controllers\UserController;
use App\Core\DI\Container;
use App\Core\Router\Router;

require_once __DIR__ . '/vendor/autoload.php';

const VIEW_PATH = __DIR__ . '/View';

$container = new Container();
$router = new Router($container);

$router->registerFromAttribute([
    OrderController::class,
    ProductController::class,
    UserController::class,
]);

echo "Var dumping routes:" . '<br/>';
var_dump($router->routes());

echo "Request URI: " . $_SERVER['REQUEST_URI'] . '<br />';
echo "Request Method: " . $_SERVER['REQUEST_METHOD'];

(new App(
    $container,
    $router,
    ['uri' => $_SERVER['REQUEST_URI'], 'method' => $_SERVER['REQUEST_METHOD']],
))->run();

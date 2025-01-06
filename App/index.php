<?php

declare(strict_types=1);

use App\App;
use App\Core\DI\Container;
use App\Core\Router\Router;

require_once __DIR__ . '/vendor/autoload.php';

const VIEW_PATH = __DIR__ . '/Views';

$container = new Container();
$router = new Router($container);
$router->autoloadRoutes();

echo "Var dumping routes:" . '<br/>';
echo '<pre>';
print_r($router->routes());
echo '</pre>';

echo "Request URI: " . $_SERVER['REQUEST_URI'] . '<br />';
echo "Request Method: " . $_SERVER['REQUEST_METHOD'];

(new App(
    $container,
    $router,
    ['uri' => $_SERVER['REQUEST_URI'], 'method' => $_SERVER['REQUEST_METHOD']],
))->run();

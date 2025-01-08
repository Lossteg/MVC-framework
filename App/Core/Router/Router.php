<?php

declare(strict_types=1);

namespace App\Core\Router;

use App\Core\Attributes\Route;
use App\Core\Attributes\RouteGroup;
use App\Core\DI\Container;
use App\Core\Exceptions\RouteNotFoundException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use RegexIterator;
use RuntimeException;

class Router
{
    private array $routes = [];
    private const CONTROLLER_NAMESPACE = 'App\\Controllers\\';
    private const CONTROLLER_PATH = __DIR__ . '/../../Controllers';

    public function __construct(private Container $container)
    {
    }

    public function autoloadRoutes(): void
    {
        $files = $this->scanControllerDirectory();
        $this->registerControllers($files);
    }

    private function scanControllerDirectory(): RegexIterator
    {
        $absolutePath = realpath(self::CONTROLLER_PATH);

        if (!$absolutePath) {
            throw new RuntimeException('Controllers directory not found');
        }

        $directory = new RecursiveDirectoryIterator(
            $absolutePath,
            FilesystemIterator::SKIP_DOTS
        );
        $iterator = new RecursiveIteratorIterator($directory);

        return new RegexIterator(
            $iterator,
            '/^.+Controller\.php$/i',
            RegexIterator::GET_MATCH
        );
    }

    public function registerControllers(RegexIterator $files): void
    {
        foreach ($files as $file) {
            $fileName = $file[0];
            // Убираем расширение .php
            $className = pathinfo($fileName, PATHINFO_FILENAME);

            $className = self::CONTROLLER_NAMESPACE . $className;

            if (!class_exists($className)) {
                echo "Class does not exist: " . $className . "<br>";
                continue;
            }

            // Работаем с существующим классом контроллера
            $reflectionController = new ReflectionClass($className);
            $groupPrefix = $this->getGroupPrefix($reflectionController);

            foreach ($reflectionController->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    $path = $this->buildRoutePath($groupPrefix, $route->routePath);
                    $this->register($route->method->value, $path, [$className, $method->getName()]);
                }
            }
        }
    }

    private function getGroupPrefix(ReflectionClass $controller): string
    {
        $groupAttributes = $controller->getAttributes(RouteGroup::class);
        if (!empty($groupAttributes)) {
            return $groupAttributes[0]->newInstance()->prefix;
        }
        return '';
    }

    private function buildRoutePath(string $prefix, string $path): string
    {
        return $this->normalizePath($prefix . $path);
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

    public function register(string $requestMethod, string $route, callable|array $action): self
    {
        $normalizedRoute = $this->normalizePath($route);
        $segments = explode('/', trim($normalizedRoute, '/'));
        $parameters = [];

        // Находим параметры в сегментах пути
        foreach ($segments as $i => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $paramName = trim($segment, '{}');
                $parameters[$i] = $paramName;
            }
        }

        $this->routes[$requestMethod][$normalizedRoute] = [
            'segments' => $segments,
            'parameters' => $parameters,
            'action' => $action
        ];

        return $this;
    }

    public function resolve(string $requestUri, string $requestMethod)
    {
        $path = $this->normalizePath(explode('?', $requestUri)[0]);
        $requestSegments = explode('/', trim($path, '/'));

        foreach ($this->routes[$requestMethod] ?? [] as $route => $routeData) {
            if ($match = $this->matchRoute($requestSegments, $routeData)) {
                $action = $routeData['action'];

                if (is_callable($action)) {
                    return call_user_func_array($action, array_values($match['parameters']));
                }

                [$class, $method] = $action;

                if (class_exists($class)) {
                    $controller = $this->container->get($class);

                    if (method_exists($controller, $method)) {
                        return call_user_func_array(
                            [$controller, $method],
                            array_values($match['parameters'])
                        );
                    }
                }
            }
        }

        throw new RouteNotFoundException();
    }

    private function matchRoute(array $requestSegments, array $routeData): false|array
    {
        $routeSegments = $routeData['segments'];

        // Проверяем количество сегментов
        if (count($requestSegments) !== count($routeSegments)) {
            return false;
        }

        $parameters = [];

        // Проверяем каждый сегмент
        foreach ($routeSegments as $i => $routeSegment) {
            $requestSegment = $requestSegments[$i];

            // Если это параметр
            if (isset($routeData['parameters'][$i])) {
                $paramName = $routeData['parameters'][$i];

                // Если параметр должен быть числом
                if (str_contains($paramName, 'id')) {
                    if (!is_numeric($requestSegment)) {
                        return false;
                    }
                    $parameters[$paramName] = (int)$requestSegment;
                } else {
                    $parameters[$paramName] = $requestSegment;
                }
                continue;
            }

            // Если это статический сегмент
            if ($routeSegment !== $requestSegment) {
                return false;
            }
        }

        return ['parameters' => $parameters];
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
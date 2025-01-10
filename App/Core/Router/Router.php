<?php

declare(strict_types=1);

namespace App\Core\Router;

use App\Core\Attributes\AccessRecieve;
use App\Core\Attributes\Route;
use App\Core\Attributes\RouteGroup;
use App\Core\DI\Container;
use App\Core\Exceptions\RouteNotFoundException;
use App\Core\Middlewares\AuthMiddleware;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RegexIterator;
use RuntimeException;

class Router
{
    /**
     * @var array<string, array<string, array{
     *     segments: array<int, string>,
     *     parameters: array<int, string>,
     *     action: callable|array{0: class-string, 1: string}
     * }>>
     */
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

    /**
     * @return RegexIterator<string, array<int, string>, RecursiveIteratorIterator<RecursiveDirectoryIterator>>
     */
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

        /** @var RegexIterator<string, array<int, string>, RecursiveIteratorIterator<RecursiveDirectoryIterator>>*/
        return new RegexIterator(
            $iterator,
            '/^.+Controller\.php$/i',
            RegexIterator::GET_MATCH
        );
    }

    /**
     * @param RegexIterator<string, array<int, string>, RecursiveIteratorIterator<RecursiveDirectoryIterator>> $files
     */
    public function registerControllers(RegexIterator $files): void
    {
        foreach ($files as $file) {
            $fileName = $file[0];
            $className = pathinfo($fileName, PATHINFO_FILENAME);
            $className = self::CONTROLLER_NAMESPACE . $className;

            if (!class_exists($className)) {
                echo 'Class does not exist: ' . $className . '<br>';
                continue;
            }

            /** @var class-string $className */
            $reflectionController = new ReflectionClass($className);
            $groupPrefix = $this->getGroupPrefix($reflectionController);

            foreach ($reflectionController->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();
                    $path = $this->buildRoutePath($groupPrefix, $route->routePath);
                    /** @var array{0: class-string, 1: string} */
                    $action = [$className, $method->getName()];
                    $this->register($route->method->value, $path, $action);
                }
            }
        }
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $controller
     */
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

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function register(string $requestMethod, string $route, callable|array $action): self
    {
        $normalizedRoute = $this->normalizePath($route);
        $segments = explode('/', trim($normalizedRoute, '/'));
        $parameters = [];

        foreach ($segments as $i => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $paramName = trim($segment, '{}');
                $parameters[$i] = $paramName;
            }
        }

        $this->routes[$requestMethod][$normalizedRoute] = [
            'segments' => $segments,
            'parameters' => $parameters,
            'action' => $action,
        ];

        return $this;
    }

    /**
     * @return mixed
     */
    public function resolve(string $requestUri, string $requestMethod): mixed
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
                    /** @var callable */
                    $callback = [$controller, $method];

                    if (method_exists($controller, $method)) {
                        // Проверка атрибута #[AccessRecieve] на уровне класса
                        $reflectionClass = new ReflectionClass($class);
                        $classHasAuth = !empty($reflectionClass->getAttributes(AccessRecieve::class));

                        // Проверка атрибута #[AccessRecieve] на уровне метода
                        $reflectionMethod = new ReflectionMethod($class, $method);
                        $methodHasAuth = !empty($reflectionMethod->getAttributes(AccessRecieve::class));

                        if ($classHasAuth || $methodHasAuth) {
                            AuthMiddleware::check();
                        }

                        return call_user_func_array($callback, array_values($match['parameters']));
                    }
                }
            }
        }

        throw new RouteNotFoundException();
    }

    /**
     * @param array<int, string> $requestSegments
     * @param array{
     *     segments: array<int, string>,
     *     parameters: array<int, string>,
     *     action: callable|array{0: class-string, 1: string}
     * } $routeData
     * @return array{parameters: array<string, string|int>}|false
     */
    private function matchRoute(array $requestSegments, array $routeData): array|false
    {
        $routeSegments = $routeData['segments'];

        if (count($requestSegments) !== count($routeSegments)) {
            return false;
        }

        $parameters = [];

        foreach ($routeSegments as $i => $routeSegment) {
            $requestSegment = $requestSegments[$i];

            if (isset($routeData['parameters'][$i])) {
                $paramName = $routeData['parameters'][$i];

                if (str_contains($paramName, 'id')) {
                    if (!is_numeric($requestSegment)) {
                        return false;
                    }
                    $parameters[$paramName] = (int) $requestSegment;
                } else {
                    $parameters[$paramName] = $requestSegment;
                }
                continue;
            }

            if ($routeSegment !== $requestSegment) {
                return false;
            }
        }

        return ['parameters' => $parameters];
    }

    /**
     * @return array<string, array<string, array{
     *     segments: array<int, string>,
     *     parameters: array<int, string>,
     *     action: callable|array{0: class-string, 1: string}
     * }>>
     */
    public function routes(): array
    {
        return $this->routes;
    }
}

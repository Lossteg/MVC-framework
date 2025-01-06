<?php

declare(strict_types=1);

namespace App\Core\DI;

use ReflectionClass;
use ReflectionException;

class Container implements ContainerInterface
{
    protected array $services = [];        // Зарегистрированные объекты
    protected array $aliases = [];         // (интерфейсы => реализации)
    protected array $instances = [];       // Уже созданные объекты
    protected array $parameters = [];
    protected array $globalParameters = [];
    protected array $resolving = []; //Отслеживает текущие зависимости в процесссе разрешения

    public function set(string $id, string $service): void
    {
        if(isset($this->services[$id]) || isset($this->aliases[$id])) {
            throw new \InvalidArgumentException(
                \sprintf('The "%s" service is already initialized, you cannot replace it.', $id)
            );
        }

        if (interface_exists($id)) {
            if (!in_array($id, class_implements($service))) {
                throw new \Exception("Class '$service' must implement interface '$id'.");
            }

            $this->aliases[$id] = $service;
        }

        if (class_exists($id)) {
            $this->services[$id] = $service;
        }
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->aliases[$id]);
    }

    public function get(string $id): ?object
    {
        $id = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->services[$id])) {
            return $this->instances[$id] = $this->autowire($this->services[$id]);
        }

        /*
        Вот это вот всё снизу только для создания ленивой загрузки
            В set мы регаем, но применяем только там, где надо
            т.е. через этот метод гет
        */

        //Если передали имя класса
        if (class_exists($id)) {
            try {
                return $this->instances[$id] = $this->autowire($id);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        //Если передали интерфейс
        if(interface_exists($id)) {
            try{
                $implementation = $this->resolveInterface($id);

                return $this->instances[$id] = $this->get($implementation);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        throw new \Exception("Service '$id' not found.");
    }

    public function setAlias(string $alias, ?string $service): void
    {
        if ($alias === $service) {
            throw new \InvalidArgumentException("An alias cannot reference itself: '$alias'.");
        }
        //Потом потести на случай сравнения объектов
        if (isset($this->aliases[$alias]) && $this->aliases[$alias] === $service) {
            throw new \InvalidArgumentException(
                "The alias '$alias' was already set for this service '$service'."
            );
        }
        if ($service) {
            $this->aliases[$alias] = $service;
        }
    }

    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    public function setGlobalParameter(string $name, mixed $value): void
    {
        $this->globalParameters[$name] = $value;
    }

    public function setParameters(string $class, array $parameters): void
    {
        if (!isset($this->parameters[$class])) {
            $this->parameters[$class] = [];
        }

        $this->parameters[$class] = array_merge($this->parameters[$class], $parameters);
    }

    private function resolveParameter(mixed $value, ?string $class = null): mixed
    {
        if (\is_string($value) && str_contains($value, '%')) {
            $key = trim($value, '%');

            if (isset($this->globalParameters[$key])) {
                return $this->globalParameters[$key];
            }

            if ($class !== null && isset($this->parameters[$class][$key])) {
                return $this->parameters[$class][$key];
            }

            throw new \Exception("Parameter '$key' not found.");
        }

        return $value;
    }

    private function resolveInterface(string $interface): ?string
    {
        if(isset($this->aliases[$interface])) {
            return $this->aliases[$interface];
        }

        $implementation = str_replace('Interface', '', $interface);

        if (class_exists($implementation) && in_array($interface, class_implements($implementation))) {
            return $implementation;
        } elseif (!class_exists($implementation)) {
            throw new \Exception("Class '$implementation' not found.");
        } elseif(!in_array($interface, class_implements($implementation))) {
            throw new \Exception("Class '$implementation' must implement interface '$interface'.");
        } else {
            throw new \Exception("No implementation registered or found for interface '$interface'.");
        }
    }

    private function resolveConstructorParameters(\ReflectionParameter $parameter, ?string $class = null): array|object
    {
        $type = $parameter->getType();

        // Если параметр имеет тип
        if ($type instanceof \ReflectionNamedType) {
            try {
                $typeName = $type->getName();

                // Если это не встроенный тип, пытаемся разрешить зависимость
                if (!$type->isBuiltin()) {
                    return $this->get($typeName);
                }

                // Если это встроенный тип, ищем параметр в глобальных или локальных параметрах
                if ($class !== null && isset($this->parameters[$class][$parameter->getName()])) {
                    return $this->resolveParameter($this->parameters[$class][$parameter->getName()], $class);
                }

                if (isset($this->globalParameters[$parameter->getName()])) {
                    return $this->resolveParameter($this->globalParameters[$parameter->getName()]);
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        // Если есть значение по умолчанию, используем его
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception("Cannot resolve parameter '{$parameter->getName()}' for class '$class'.");
    }

    private function autowire(string $id) : object
    {
        if (isset($this->resolving[$id])) {
            throw new \InvalidArgumentException("Cyclic dependency detected while resolving '$id'.");
        }

        $this->resolving[$id] = true;

        try {
            $reflectionClass = new ReflectionClass($id);

            if (!$reflectionClass->isInstantiable()) {
                throw new \Exception("Class '$id' is not instantiable.");
            }

            $constructor = $reflectionClass->getConstructor();
            if (!$constructor) {
                return new $id();
            }

            $dependencies = [];
            foreach ($constructor->getParameters() as $parameter) {
                $dependencies[] = $this->resolveConstructorParameters($parameter, $id);
            }

            unset($this->resolving[$id]);
            return $reflectionClass->newInstanceArgs($dependencies);
        } catch (\Exception $e) {
            throw new \Exception("Class '$id' not found: {$e->getMessage()}");
        }
    }
}
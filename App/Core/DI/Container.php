<?php

declare(strict_types=1);

namespace App\Core\DI;

use ReflectionClass;

class Container implements ContainerInterface
{
    /** @var array<string, class-string> */
    protected array $services = [];

    /** @var array<string, string> */
    protected array $aliases = [];

    /** @var array<string, object> */
    protected array $instances = [];

    /** @var array<string, array<string, mixed>> */
    protected array $parameters = [];

    /** @var array<string, mixed> */
    protected array $globalParameters = [];

    /** @var array<string, bool> */
    protected array $resolving = [];

    /**
     * @param class-string|string $id
     * @param class-string $service
     * @throws \Exception
     */
    public function set(string $id, string $service): void
    {
        if (isset($this->services[$id]) || isset($this->aliases[$id])) {
            throw new \InvalidArgumentException(
                \sprintf('The "%s" service is already initialized, you cannot replace it.', $id)
            );
        }

        if (interface_exists($id)) {
            $implements = class_implements($service);
            if ($implements === false || !in_array($id, $implements, true)) {
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

    /**
     * @param string $id
     * @return object
     * @throws \Exception
     */
    public function get(string $id): object
    {
        $serviceId = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$serviceId])) {
            return $this->instances[$serviceId];
        }

        if (isset($this->services[$serviceId])) {
            /** @var class-string $serviceClass */
            $serviceClass = $this->services[$serviceId];
            return $this->instances[$serviceId] = $this->autowire($serviceClass);
        }

        if (class_exists($serviceId)) {
            try {
                /** @var class-string $serviceId */
                return $this->instances[$serviceId] = $this->autowire($serviceId);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        if (interface_exists($serviceId)) {
            try {
                $implementation = $this->resolveInterface($serviceId);
                return $this->instances[$serviceId] = $this->get($implementation);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        throw new \Exception("Service '$serviceId' not found.");
    }

    public function setAlias(string $alias, ?string $service): void
    {
        if ($alias === $service) {
            throw new \InvalidArgumentException("An alias cannot reference itself: '$alias'.");
        }

        if (isset($this->aliases[$alias]) && $this->aliases[$alias] === $service) {
            throw new \InvalidArgumentException(
                "The alias '$alias' was already set for this service '$service'."
            );
        }

        if ($service !== null) {
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

    /**
     * @param array<string, mixed> $parameters
     */
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

    private function resolveInterface(string $interface): string
    {
        if (isset($this->aliases[$interface])) {
            return $this->aliases[$interface];
        }

        $implementation = str_replace('Interface', '', $interface);

        $implements = class_implements($implementation);
        if (class_exists($implementation) && $implements !== false && in_array($interface, $implements, true)) {
            return $implementation;
        }

        if (!class_exists($implementation)) {
            throw new \Exception("Class '$implementation' not found.");
        }

        $implements = class_implements($implementation);
        if ($implements === false || !in_array($interface, $implements, true)) {
            throw new \Exception("Class '$implementation' must implement interface '$interface'.");
        }

        throw new \Exception("No implementation registered or found for interface '$interface'.");
    }

    private function resolveConstructorParameters(\ReflectionParameter $parameter, ?string $class = null): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            try {
                $typeName = $type->getName();

                if (!$type->isBuiltin()) {
                    return $this->get($typeName);
                }

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

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception("Cannot resolve parameter '{$parameter->getName()}' for class '$class'.");
    }

    /**
     * @param class-string $id
     * @throws \Exception
     */
    private function autowire(string $id): object
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
                unset($this->resolving[$id]);
                return new $id();
            }

            $dependencies = [];
            foreach ($constructor->getParameters() as $parameter) {
                $dependencies[] = $this->resolveConstructorParameters($parameter, $id);
            }

            unset($this->resolving[$id]);
            return $reflectionClass->newInstanceArgs($dependencies);
        } catch (\Exception $e) {
            unset($this->resolving[$id]);
            throw new \Exception("Class '$id' not found: {$e->getMessage()}");
        }
    }
}

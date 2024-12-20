<?php

declare(strict_types=1);

namespace App\Core\DI;

interface ContainerInterface
{
    public function set(string $id, string $service): void;
    public function get(string $id): ?object;
    public function has(string $id): bool;
}